package main

import (
	"database/sql"
	"encoding/json"
	"log"
	"net/http"

	_ "github.com/go-sql-driver/mysql"
)

var db *sql.DB

type Response struct {
	Message string  `json:"message,omitempty"`
	Balance float64 `json:"balance,omitempty"`
}

func initDB() {
	var err error
	// Ganti user:pass@/dbname sesuai pengaturan MySQL kamu
	db, err = sql.Open("mysql", "root:@tcp(127.0.0.1:3306)/uangkas_db")
	if err != nil {
		log.Fatal("Gagal koneksi database:", err)
	}
	if err = db.Ping(); err != nil {
		log.Fatal("Tidak bisa terhubung ke database:", err)
	}
	log.Println("Terhubung ke database!")
}

func getBalance(w http.ResponseWriter, r *http.Request) {
	var balance float64
	err := db.QueryRow("SELECT IFNULL(SUM(CASE WHEN type = 'income' THEN amount WHEN type = 'expense' THEN -amount END), 0) FROM transactions").Scan(&balance)
	if err != nil {
		http.Error(w, "Gagal mengambil saldo", http.StatusInternalServerError)
		return
	}

	resp := Response{Balance: balance}
	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(resp)
}

func addTransaction(w http.ResponseWriter, r *http.Request, txType string) {
	var data struct {
		Amount float64 `json:"amount"`
	}

	if err := json.NewDecoder(r.Body).Decode(&data); err != nil || data.Amount <= 0 {
		http.Error(w, "Jumlah tidak valid", http.StatusBadRequest)
		return
	}

	// Tambahkan ke tabel transaksi
	_, err := db.Exec("INSERT INTO transactions (amount, type) VALUES (?, ?)", data.Amount, txType)
	if err != nil {
		http.Error(w, "Gagal menyimpan transaksi", http.StatusInternalServerError)
		return
	}

	getBalance(w, r) // balikin saldo terbaru
}

func editTransaction(w http.ResponseWriter, r *http.Request) {
    var data struct {
        ID     int64   `json:"id"`
        Amount float64 `json:"amount"`
        Type   string  `json:"type"` // "income" atau "expense"
    }

    if err := json.NewDecoder(r.Body).Decode(&data); err != nil {
        http.Error(w, "Data tidak valid", http.StatusBadRequest)
        return
    }

    if data.ID <= 0 || data.Amount <= 0 || (data.Type != "income" && data.Type != "expense") {
        http.Error(w, "Input tidak valid", http.StatusBadRequest)
        return
    }

    // Update data transaksi
    res, err := db.Exec("UPDATE transactions SET amount = ?, type = ? WHERE id = ?", data.Amount, data.Type, data.ID)
    if err != nil {
        http.Error(w, "Gagal update transaksi", http.StatusInternalServerError)
        return
    }

    rowsAffected, err := res.RowsAffected()
    if err != nil || rowsAffected == 0 {
        http.Error(w, "Transaksi tidak ditemukan atau tidak berubah", http.StatusNotFound)
        return
    }

    getBalance(w, r) // kirim saldo terbaru
}

func deleteTransaction(w http.ResponseWriter, r *http.Request) {
    var data struct {
        ID int64 `json:"id"`
    }

    if err := json.NewDecoder(r.Body).Decode(&data); err != nil || data.ID <= 0 {
        http.Error(w, "ID tidak valid", http.StatusBadRequest)
        return
    }

    res, err := db.Exec("DELETE FROM transactions WHERE id = ?", data.ID)
    if err != nil {
        http.Error(w, "Gagal menghapus transaksi", http.StatusInternalServerError)
        return
    }

    rowsAffected, err := res.RowsAffected()
    if err != nil || rowsAffected == 0 {
        http.Error(w, "Transaksi tidak ditemukan", http.StatusNotFound)
        return
    }

    getBalance(w, r) // kirim saldo terbaru
}


func addIncome(w http.ResponseWriter, r *http.Request) {
	addTransaction(w, r, "income")
}

func addExpense(w http.ResponseWriter, r *http.Request) {
	addTransaction(w, r, "expense")
}

func getHistory(w http.ResponseWriter, r *http.Request) {
	rows, err := db.Query("SELECT created_at, type, amount FROM transactions ORDER BY created_at DESC")
	if err != nil {
		http.Error(w, "Gagal mengambil data", http.StatusInternalServerError)
		return
	}
	defer rows.Close()

	var history []map[string]interface{}
	for rows.Next() {
		var createdAt, txType string
		var amount float64
		if err := rows.Scan(&createdAt, &txType, &amount); err != nil {
			http.Error(w, "Gagal membaca data", http.StatusInternalServerError)
			return
		}
		history = append(history, map[string]interface{}{
			"created_at": createdAt,
			"type":       txType,
			"amount":     amount,
		})
	}

	w.Header().Set("Content-Type", "application/json")
	json.NewEncoder(w).Encode(history)
}

func main() {
	initDB()

	http.HandleFunc("/balance", getBalance)
	http.HandleFunc("/income", addIncome)
	http.HandleFunc("/expense", addExpense)
	http.HandleFunc("/history", getHistory) 
	http.HandleFunc("/edit", editTransaction)
	http.HandleFunc("/delete", deleteTransaction)


	log.Println("Server running at :8080")
	log.Fatal(http.ListenAndServe(":8080", nil))
}


