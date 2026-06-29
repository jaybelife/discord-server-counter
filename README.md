# Discord Server Counter

Ein kleines PHP Tool, das Discord Bot Informationen und alle Server (Guilds) eines Bots anzeigt.

---

## 📌 Features

- Login mit Discord Bot Token
- Anzeige von Bot Username + ID
- Anzeige aller Server (Guilds), in denen der Bot ist
- Alphabetisch sortierte Serverliste
- Modernes Dark UI

---

## 🔐 Sicherheit

- Token wird **nicht gespeichert**
- Token wird nur für API Requests genutzt
- CSRF Schutz ist integriert
- Keine Datenbank erforderlich

---

## ⚙️ Funktionsweise

Das Tool nutzt die Discord API:

- `/users/@me`
- `/users/@me/guilds`

Die Daten werden serverseitig mit PHP (cURL) abgefragt und im Browser dargestellt.

---

## 🖼️ Screenshots
<img width="1920" height="330" alt="grafik" src="https://github.com/user-attachments/assets/19d64fa7-cf71-4d92-b7ff-60aa9d938e24" />
<img width="1920" height="621" alt="grafik" src="https://github.com/user-attachments/assets/7614f6af-6e18-4fbe-a358-eddddfef8d31" />

---

## ⚠️ Hinweis

Kein offizielles Discord Produkt.

---

## 👤 Credits

Made by .jaybelife  
GitHub: https://github.com/jaybelife

**Contributors:**
- [Marco](https://github.com/Marco12223)


---

## 📄 License

Dieses Projekt ist unter der MIT License veröffentlicht.
