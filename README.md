# QR Darovací Formulář

**WordPress plugin pro generování QR kódu pro darování.**  
Umožňuje správu více účtů, předdefinované zprávy pro příjemce a generování QR kódu přímo v prohlížeči dle [specifikace České QR platby (SPayD)](https://qr-platba.cz/pro-vyvojare/specifikace-formatu/) – bez závislosti na externím API.

---

## ✨ Funkce

- ✅ Více bankovních účtů
- ✅ Předdefinované zprávy s proměnnou `{{jmeno}}`
- ✅ Výběr účtu a poznámky na frontendu
- ✅ Generování QR platby lokálně v prohlížeči (SPayD formát)
- ✅ Automatický výpočet IBAN z čísla účtu a kódu banky
- ✅ Podpora prefixu účtu
- ✅ Uživatelsky přívětivá administrace
- ✅ Jednoduchý shortcode

---
## 🖼️ Ukázka

- Z pohledu uživatele:
  ![qr_frontend](https://github.com/user-attachments/assets/efd94037-a809-489e-80b7-eaca75863fba)
- Z pohledu admina webu:
  ![qr_admin1](https://github.com/user-attachments/assets/f666559f-4bd6-454c-8519-9c2cc7f15868)


---

## 🔧 Použití

### Shortcode

```
[qr_darovaci_formular]
```

> 📌 Zobrazí formulář pro výběr účtu, poznámky, zadání jména a částky.

### Parametr `ucet`

```
[qr_darovaci_formular ucet="0"]
```

- Pokud nechceš výběr účtu na frontendu, zadej index (např. `0` pro první účet).
- Hodí se pro vložení různých formulářů na různé stránky.

---

## ⚙️ Nastavení v administraci

Najdeš v **Nastavení > QR Darovací Formulář**:

- přidání/odebrání účtů
- číslo účtu, prefix účtu (nepovinný) a kód banky
- seznam poznámek (jedna poznámka na řádek)
- poznámka může obsahovat `{{jmeno}}`, která bude nahrazena jménem dárce

---

## 🛠️ Technické detaily

- QR kód je generován knihovnou [qrcode.js](https://github.com/davidshimjs/qrcodejs) uloženou lokálně v pluginu (`js/qrcode.min.js`)
- Číslo účtu je automaticky převedeno na CZ IBAN (algoritmus ISO 7064 mod97)
- QR kód odpovídá standardu [České QR platby (SPD 1.0)](https://qr-platba.cz/pro-vyvojare/specifikace-formatu/) – kompatibilní se všemi českými bankovními aplikacemi

---

## 🛣️ Roadmap

- Přidání slovenského formátu QR platby
- Optimalizace pro oficiální Wordpress repozitář
---

## 🧠 Využití

Plugin vznikl pro neziskovou organizaci [Šťastný úsměv, z.s.](https://stastny-usmev.cz),  
ale je použitelný pro všechny projekty, které chtějí přijímat dary s předdefinovanými poznámkami jednoduše přes QR kód.

---
## 🫶 Poděkování

Plugin obsahuje [qrcode.js od davidshimjs](https://github.com/davidshimjs/qrcodejs)

---
### 📦 Stažení pluginu

Aktuální stabilní verze: [v2.1.1 – Oprava grafického zobrazení](https://github.com/adamhornofmedia/QR-Darovaci-Formular/releases)
