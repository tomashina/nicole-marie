<?php
// Naslov
$_['heading_title'] = 'Nicole Marie – Sidrene cijene';

// Tekst
$_['text_extension'] = 'Proširenja';
$_['text_list'] = 'Registar sidrenih cijena';
$_['text_edit'] = 'Uredi sidrenu cijenu';
$_['text_filter'] = 'Filtri';
$_['text_publications'] = 'Dnevni cjenici';
$_['text_settings'] = 'Postavke i automatizacija';
$_['text_no_results'] = 'Nema pronađenih zapisa.';
$_['text_all_statuses'] = 'Svi statusi';
$_['text_status_confirmed'] = 'Potvrđeno';
$_['text_status_pending'] = 'Čeka provjeru';
$_['text_status_disabled'] = 'Isključeno';
$_['text_system'] = 'Sustav';
$_['text_missing_count'] = 'Aktivnih artikala bez sidrene cijene: %s';
$_['text_success_edit'] = 'Uspješno: Sidrena cijena i revizijski trag su ažurirani.';
$_['text_success_sync'] = 'Uspješno: Kreirano je %s nedostajućih sidrenih cijena.';
$_['text_success_publish'] = 'Uspješno: Objavljen je cjenik: %s';
$_['text_success_settings'] = 'Uspješno: Postavke sidrenih cijena su spremljene.';
$_['text_success_import'] = 'CSV uvoz je dovršen: ažurirano %s, bez promjene %s zapisa.';
$_['text_reference_rule'] = 'Artikli objavljeni do uključivo 10. rujna 2026. koriste 2026-09-10. Kasniji artikli koriste stvarni datum prve objave.';
$_['text_cron_help'] = 'Pozovite URL svaki dan prije 08:00 po vremenu Europe/Zagreb i pošaljite ključ u HTTP zaglavlju X-Anchor-Price-Key. Objavljuje jedan dnevni cjenik web trgovine.';
$_['text_audit'] = 'Revizijski trag';
$_['text_csv_exchange'] = 'Masovni CSV unos';
$_['text_csv_exchange_help'] = 'Najprije izvezite postojeće zapise, uredite cijene, referentni datum ili status i zatim vratite CSV. ID proizvoda je obvezan i ne smije se ponavljati. Cijeli se uvoz odbija ako bilo koji red nije ispravan.';

// Stupci
$_['column_product'] = 'Artikl';
$_['column_model'] = 'Model / SKU';
$_['column_net_price'] = 'Neto sidrena cijena';
$_['column_gross_price'] = 'Bruto sidrena cijena';
$_['column_reference_date'] = 'Referentni datum';
$_['column_status'] = 'Status';
$_['column_action'] = 'Radnja';
$_['column_location'] = 'Prodajno mjesto';
$_['column_sequence'] = 'Redni broj';
$_['column_filename'] = 'Datoteka';
$_['column_products'] = 'Artikala';
$_['column_published'] = 'Objavljeno';
$_['column_user'] = 'Korisnik';
$_['column_reason'] = 'Razlog';
$_['column_before'] = 'Prije';
$_['column_after'] = 'Poslije';
$_['column_date_added'] = 'Datum';

// Polja
$_['entry_filter_name'] = 'Naziv artikla';
$_['entry_filter_model'] = 'Model / SKU';
$_['entry_filter_status'] = 'Status provjere';
$_['entry_date_from'] = 'Referentni datum od';
$_['entry_date_to'] = 'Referentni datum do';
$_['entry_price'] = 'Neto iznos';
$_['entry_gross_price'] = 'Bruto iznos';
$_['entry_reference_date'] = 'Referentni datum';
$_['entry_verification_status'] = 'Status provjere';
$_['entry_reason'] = 'Razlog promjene';
$_['entry_default_unit'] = 'Zadana prodajna jedinica';
$_['entry_cron_url'] = 'URL dnevnog cron zadatka';
$_['entry_cron_key'] = 'Cron ključ';
$_['entry_import_file'] = 'CSV datoteka';
$_['entry_import_reason'] = 'Razlog masovne promjene';

// Gumbi
$_['button_filter'] = 'Filtriraj';
$_['button_clear'] = 'Očisti';
$_['button_sync'] = 'Kreiraj nedostajuće sidrene cijene';
$_['button_publish'] = 'Objavi CSV cjenik';
$_['button_settings'] = 'Spremi postavke';
$_['button_download'] = 'Preuzmi';
$_['button_export_csv'] = 'Izvezi CSV za uređivanje';
$_['button_import_csv'] = 'Uvezi i provjeri CSV';

// Pomoć
$_['help_reason'] = 'Obvezno. Razlog se trajno čuva u revizijskom tragu.';
$_['help_gross_price'] = 'Bruto snapshot s porezom. Mijenjajte samo kada je potrebno ispraviti sam spremljeni snapshot.';
$_['help_default_unit'] = 'Koristi se za sve artikle jer trgovina nema strukturirano polje prodajne jedinice. Zadano: kom.';
$_['help_import_reason'] = 'Obvezno. Isti se razlog sprema uz svaki promijenjeni zapis u revizijskom tragu.';

// Upozorenja i greške
$_['warning_publication_due'] = 'Dnevni cjenik nije objavljen do 08:00.';
$_['error_permission'] = 'Upozorenje: Nemate ovlasti za izmjenu modula Sidrene cijene.';
$_['error_not_installed'] = 'Tablice modula ne postoje. Najprije instalirajte modul kroz Proširenja.';
$_['error_not_found'] = 'Tražena sidrena cijena nije pronađena.';
$_['error_price'] = 'Unesite ispravan nenegativan neto iznos.';
$_['error_gross_price'] = 'Unesite ispravan nenegativan bruto iznos.';
$_['error_reference_date'] = 'Unesite ispravan datum u obliku GGGG-MM-DD.';
$_['error_status'] = 'Odaberite ispravan status provjere.';
$_['error_reason'] = 'Razlog mora sadržavati između 3 i 255 znakova.';
$_['error_default_unit'] = 'Zadana jedinica mora sadržavati između 1 i 16 znakova.';
$_['error_file_missing'] = 'Datoteka objave nije dostupna ili joj je istekao rok čuvanja od 30 dana.';
$_['error_export'] = 'Nije moguće izraditi CSV izvoz sidrenih cijena.';
$_['error_import_file'] = 'Odaberite valjanu CSV datoteku za uvoz.';
$_['error_import_size'] = 'CSV datoteka mora biti manja od 5 MB.';
$_['error_import_type'] = 'Dopuštene su samo datoteke s nastavkom .csv.';
$_['error_import_reason'] = 'Razlog uvoza mora sadržavati između 3 i 255 znakova.';
