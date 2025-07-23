=== QR Darovací Formulář ===
Contributors: adamhornof
Donate link: https://www.adamhornof.cz
Tags: qr platba, dar, donation, paylibo, qr kód, platba
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Vložte na svůj web jednoduchý formulář pro QR platbu s vlastním účtem a zprávou pro příjemce. Podporuje více účtů a dynamické poznámky.

== Description ==

Tento plugin umožňuje vložit darovací formulář na web pomocí shortcodu `[qr_darovaci_formular]`. Uživatel zadá své jméno a částku, QR kód se automaticky vygeneruje pomocí API služby Paylibo.

V administraci můžete:
- Nastavit více účtů (např. sbírkový, provozní)
- U každého účtu určit vlastní předdefinovanou poznámku (např. `DAR – {{JMENO}}`)
- Při použití shortcodu zvolit, který účet se použije

Používá API: [Paylibo QR platba](https://api.paylibo.com)

== Usage ==

Základní shortcode:
[qr_darovaci_formular]
Shortcode pro konkrétní účet (název musí odpovídat zadanému v administraci):
[qr_darovaci_formular ucet=“Provozní účet”]
Formulář zobrazuje pole pro jméno a částku. Po odeslání se vygeneruje QR kód s údaji podle vybraného účtu.

== Screenshots ==

1. Administrace pluginu s více účty
2. Ukázka formuláře na front-endu
3. Vygenerovaný QR kód

== Changelog ==

= 1.0 =
* První veřejná verze pluginu.
* Podpora více účtů, předdefinovaných zpráv a generování QR kódu přes Paylibo API.
* Uživatelsky přívětivá administrace.

== Installation ==

1. Nahrajte soubor `qr-darovaci-formular.zip` přes **Pluginy > Instalace pluginu > Nahrát plugin**.
2. Aktivujte plugin.
3. V menu WordPressu otevřete **QR Darovací Formulář** a nastavte účty.
4. Vložte shortcode `[qr_darovaci_formular]` na stránku, kde chcete formulář zobrazit.

== Frequently Asked Questions ==

= Jak přidám více účtů? =
V administraci klikněte na „Přidat nový účet“. Každý účet může mít jiný název, číslo účtu a zprávu pro příjemce.

= Jak vložím formulář pro konkrétní účet? =
Použijte parametr `ucet` ve shortcodu, například:
[qr_darovaci_formular ucet=“Sbírkový účet”]
= Co znamená `{{JMENO}}` v poznámce? =
Tato proměnná se automaticky nahradí jménem, které zadá návštěvník do formuláře.

== Upgrade Notice ==

= 1.0 =
První stabilní verze s podporou více účtů a proměnných poznámek.
