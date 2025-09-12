# scsvmv-student-management
# Student-Management-System-University
Responsive Student Management System for university HOD, Staff, and Admin. Features include student profiles with photos, OD/ML/DA tracking with popup alerts, internal marks leaderboard, Excel/PDF report export, and Chart.js dashboard.

# 🎓 Student Management System  

A responsive **Student Management System (SMS)** designed for universities, built for **Admin, HOD, and Staff**.  
It simplifies student record management, OD (On Duty), ML (Medical Leave), and DA (Disciplinary Action) tracking,  
internal marks leaderboard, and reporting with Excel/PDF export.  

---

## 📖 Project Summary  

This system provides:  
- 👨‍🎓 Student profiles with personal details & profile pictures  
- 📝 OD/ML/DA management with history tracking  
- ⚠️ DA popup notifications (alerts when students already have remarks)  
- 📊 Internal marks upload & auto-generated leaderboard  
- 📑 Export of records to Excel/PDF  
- 📈 Dashboard charts (OD/ML/DA trends, DA reasons distribution)  

---

## ✨ Features  

- ✅ Fully responsive (mobile + desktop)  
- ✅ Single login page (username decides role: Admin/HOD/Staff)  
- ✅ Student search by register number  
- ✅ Popup alerts for DA remarks with reason & timestamp  
- ✅ Internal marks leaderboard (auto-updated)  
- ✅ Data export to Excel/PDF  
- ✅ Graphical dashboard using Chart.js  
- ✅ UI inspired by university website  

---

## 🛠️ Tech Stack  

- **Frontend:** HTML5, CSS3, Bootstrap 5, JavaScript, Chart.js, SweetAlert2  
- **Backend:** PHP (Laravel) / Node.js (Express)  
- **Database:** MySQL  
- **Export:** PHPSpreadsheet (Excel), Dompdf/TCPDF (PDF)  

---

## 📂 Modules  

- 👨‍🎓 **Student Management** – Profiles with profile pics  
- 📝 **OD Management** – On Duty entries (reason/date)  
- 🏥 **ML Management** – Medical Leave (with certificate upload)  
- ⚠️ **DA Management** – Add remarks, popup alerts on search  
- 📊 **Internal Marks** – Upload & manage marks  
- 🏆 **Leaderboard** – Ranking by internal marks  
- 📑 **Reports** – Export data to Excel/PDF  
- 📈 **Charts** – Monthly OD/ML/DA & DA reasons  

---

## 🚀 Installation  

1. Clone the repo:  
   ```bash
   git clone https://github.com/your-username/Student-Management-System.git
   cd Student-Management-System

2.Set up database: 
 * Import sms.sql into MySQL
 * Update DB credentials in .env (Laravel) or config.js (Node)

3.Run the project:
 * Laravel: php artisan serve
 * Node.js: npm install && npm start

4.Open in browser:http://localhost:8000

