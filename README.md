# QR Darovací Formulář

**WordPress plugin pro generování QR kódu pro darování.**  
Umožňuje správu více účtů, předdefinované zprávy pro příjemce a generování QR kódu přes [Paylibo API](https://api.paylibo.com/).

---

## ✨ Funkce

- ✅ Více bankovních účtů
- ✅ Předdefinované zprávy s proměnnou `{{jmeno}}`
- ✅ Výběr účtu a poznámky na frontendu
- ✅ Generování QR platby přes Paylibo
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
- číslo účtu, kód banky
- seznam poznámek (jedna poznámka na řádek)
- poznámka může obsahovat `{{jmeno}}`, která bude nahrazena jménem dárce

---

## 🛣️ Roadmap

- Přidání slovenského formátu QR platby
- Přechod z Paylibo api na vlastní řešení aby plugin nebyl závislý na jiném serveru
- Optimalizace pro oficiální Wordpress repozitář
---
### 📦 Stažení pluginu

Aktuální stabilní verze: [v1.1 – drobné vylepšení](https://github.com/adamhornofmedia/QR-Darovaci-Formular/releases/tag/v1.1)
---

## 🧠 Využití

Plugin vznikl pro neziskovou organizaci [Šťastný úsměv, z.s.](https://stastny-usmev.cz),  
ale je použitelný pro všechny projekty, které chtějí přijímat platby jednoduše přes QR kód.
