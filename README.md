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

## 🖼️ Ukázka

> (Přidej soubor `assets/screenshot.png` do repozitáře)

```
![Screenshot pluginu](assets/screenshot.png)
```

---

## 🧠 Využití

Plugin vznikl pro neziskovou organizaci [Šťastný úsměv, z.s.](https://stastny-usmev.cz),  
ale je použitelný pro všechny projekty, které chtějí přijímat platby jednoduše přes QR kód.

---

## 🧑‍💻 Autor

Vyvinul [Adam Hornof](https://adamhornof.cz)  
IČO: 23294566

---

## ⚖️ Licence

MIT License – použijte, upravte, rozšiřujte.  
Budeme rádi za ⭐ hvězdu nebo pull request!
