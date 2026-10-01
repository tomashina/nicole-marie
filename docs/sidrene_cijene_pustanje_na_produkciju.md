# Sidrene cijene i digitalni cjenik — Nicole Marie

Ovaj modul za OpenCart 3.0.3.8 omogućuje unos i revizijski trag sidrene
cijene, prikaz na proizvodima i listama proizvoda, masovni CSV unos te jednu
dnevnu publikaciju digitalnog cjenika web trgovine. Cjenik se arhivira kao
nepromjenjivi CSV snapshot. Javni XML nastaje iz istog arhiviranog snapshota,
zato oba formata uvijek predstavljaju iste proizvode, cijene i vrijeme objave.

Modul koristi samo oznaku prodajnog kanala `WEB`. Oznake fizičkih poslovnica iz
starijih Liber Media izdanja nisu dio Nicole Marie implementacije.

## Kritična odluka prije instalacije

Referentni/cutover datum `2026-09-10` preuzet je iz zadanog Liber Media modula.
To nije tehnička činjenica o bazi Nicole Marie. Vlasnik trgovine mora pisano
potvrditi da se taj datum i tadašnje cijene smiju koristiti kao početni snapshot.

Ako datum ili povijesne cijene nisu potvrđeni:

- ne instalirati/aktivirati modul na produkciji;
- prvo pripremiti ispravan CSV s potvrđenim sidrenim cijenama i datumima ili
  dogovoriti drugi cutover te uskladiti modul prije instalacije.

## Što se pohranjuje

- jedna sidrena cijena po kombinaciji proizvoda i trgovine;
- neto cijena, prikazana/bruto cijena, valuta, porezni kontekst i referentni
  datum;
- status `confirmed`, `pending` ili `disabled`;
- audit zapis svake ručne i masovne promjene s korisnikom, razlogom, starim i
  novim podacima;
- jedan dnevni `WEB` CSV zapis s brojem proizvoda, SHA-256 kontrolnim zbrojem i
  vremenom objave.

Digitalni cjenik sadrži najmanje identifikator, naziv, šifru/model, SKU,
proizvođača, aktualnu i redovnu cijenu, sidrenu cijenu i datum, barkod,
dostupnost, količinu, status zalihe i valutu.

## Priprema produkcije

1. Potvrditi sigurnosnu kopiju baze i kompletnog `public_html` te OpenCart
   storage/download direktorija izvan web-roota.
2. Potvrditi da je PHP ekstenzija `XMLWriter` dostupna za XML izlaz te da web proces
   može pisati u OpenCartov `DIR_DOWNLOAD/anchor_price` direktorij.
3. Potvrditi vremensku zonu `Europe/Zagreb`, produkcijsku domenu, glavnu trgovinu
   (store 0), valutu EUR i HTTPS.
4. Potvrditi cutover datum `2026-09-10` ili zaustaviti puštanje.
5. Prenijeti aplikacijske datoteke. Sam prijenos datoteka ne mijenja bazu.

## Preporučena instalacija kroz OpenCart

Ovo je sigurniji način jer PHP instalacija koristi stvarni `DB_PREFIX`,
generira tajni cron ključ i bruto cijene računa OpenCartovim poreznim pravilima.

1. U administraciji otvoriti **Extensions > Extensions > Modules**.
2. Instalirati **Sidrene cijene**.
3. Otvoriti **Catalog > Sidrene cijene** i provjeriti zadani datum, jedinicu,
   broj zapisa i broj stavki koje čekaju potvrdu.
4. U **Extensions > Modifications** osvježiti izmjene te očistiti theme/cache.
5. Provjeriti broj zapisa, statuse i uzorak cijena prije prve javne objave.

Instalacija modula je idempotentna na razini sheme i čuva postojeće sidrene
cijene, audit zapise i publikacije. Deinstalacija namjerno ne briše podatke.

## Ručni unos i potvrda

U **Catalog > Sidrene cijene** moguće je filtrirati i urediti zapis. Promjena
cijene, datuma ili statusa mora imati razlog koji se sprema u audit trag.

Prije prve publikacije svi aktivni i dostupni proizvodi moraju imati potvrđen
zapis. `pending` i `disabled` blokiraju objavu za aktivni proizvod. Datum ne
smije biti u budućnosti.

## Masovni CSV unos

CSV se učitava u administraciji modula i namijenjen je ispravku ili potvrdi većeg
broja sidrenih cijena. Koristiti UTF-8 datoteku (BOM je dopušten), jedan proizvod
po retku i decimalni zapis s točkom ili zarezom. Primjer je u
`docs/anchor_price_import_example.csv`.

Kanonsko zaglavlje je:

```text
product_id,model,sku,product_name,price,gross_price,currency_code,reference_date,verification_status
```

Obvezna polja su `product_id`, `price`, `gross_price` i `reference_date`.
`product_id` je jedini autoritativni identifikator; `model`, `sku` i
`product_name` služe za ljudsku kontrolu. Dopušteni su i hrvatski aliasi:
`id_proizvoda`/`id_artikla`, `sifra`/`model`, `naziv`, `neto_cijena`,
`sidrena_cijena`/`bruto_cijena`, `valuta`,
`referentni_datum`/`datum_sidrene_cijene` i `status_provjere`.

Pravila sigurnog uvoza:

- identifikator proizvoda mora jednoznačno pronaći postojeći proizvod;
- sidrena cijena ne smije biti negativna;
- referentni datum mora biti valjani `YYYY-MM-DD` i ne smije biti u budućnosti;
- status mora biti jedna od podržanih vrijednosti;
- jedan globalni razlog uvoza obvezan je i sprema se uz svaki prihvaćeni redak;
- nepoznati proizvodi, duplikati i nevaljani retci odbijaju se i prikazuju u
  rezultatu uvoza;
- import ne mijenja redovnu niti akcijsku cijenu proizvoda;
- svaka prihvaćena promjena dobiva zaseban audit zapis.

Prije velikog uvoza napraviti probu s dva retka: jedan postojeći potvrđeni
proizvod i jedan zapis koji namjerno treba biti odbijen. Provjeriti rezultat i
audit pa tek tada učitati cijelu datoteku.

## Dnevni CSV i XML cjenik

Generator radi ovim redom:

1. sinkronizira eventualno propuštene aktivne proizvode;
2. provjerava obvezne podatke i potvrđene sidrene cijene;
3. pod zaključavanjem generira privremeni CSV;
4. atomski objavljuje dovršeni CSV i sprema SHA-256 kontrolni zbroj;
5. javni XML po zahtjevu čita upravo taj arhivirani CSV i pretvara ga u XML;
6. istječe publikacije starije od 30 dana i uklanja njihove datoteke.

Generator ne stvara drugi dnevni snapshot ako današnji valjani zapis već
postoji. CSV je izvorni arhivirani dokument; XML nije zasebno generiranje iz
trenutačne baze. Stoga kasnija promjena proizvoda tijekom dana ne može promijeniti
XML bez nove CSV publikacije.

Javna stranica arhive dostupna je rutom:

```text
https://<PRODUKCIJSKA_DOMENA>/index.php?route=information/price_list
```

Na njoj se prikazuju samo valjane `WEB` publikacije unutar 30 dana. Preuzimanje
provjerava status, dopuštenu putanju i SHA-256 prije slanja datoteke.

Stabilni javni URL-ovi za najnoviji cjenik su:

```text
https://<PRODUKCIJSKA_DOMENA>/index.php?route=information/price_list/latest&format=csv
https://<PRODUKCIJSKA_DOMENA>/index.php?route=information/price_list/latest&format=xml
```

Arhivirani format za konkretnu publikaciju koristi njezin broj:

```text
https://<PRODUKCIJSKA_DOMENA>/index.php?route=information/price_list/download&publication_id=<ID>
https://<PRODUKCIJSKA_DOMENA>/index.php?route=information/price_list/xml&publication_id=<ID>
```

## EasyCron jednom dnevno

Cron tajna se šalje isključivo u HTTP zaglavlju `X-Anchor-Price-Key`; ne stavljati
je u URL, argumente koji se javno logiraju ni dokumentaciju. Primjer:

```sh
curl --fail --silent --show-error \
  --header 'X-Anchor-Price-Key: <KLJUC_IZ_ADMINISTRACIJE>' \
  'https://<PRODUKCIJSKA_DOMENA>/index.php?route=extension/module/anchor_price/cron'
```

Postaviti jedan dnevni poziv nakon završetka uvoza/cijena, primjerice u 06:15 po
vremenu `Europe/Zagreb`. U EasyCron postavci **Method and Headers** dodati
`X-Anchor-Price-Key` s ključem prikazanim u administraciji modula; ključ ne
stavljati u URL. Prvo ga ručno pozvati i provjeriti JSON odgovor, novu
publikaciju, broj proizvoda, CSV, XML i checksum. Ponovljen poziv istog dana bez
`force` mora vratiti postojeću valjanu publikaciju, a ne stvoriti duplikat.

## Završna provjera

Provjeriti najmanje:

- proizvod s akcijom i bez akcije, proizvod s porezom i bez poreza;
- desktop i mobilni prikaz proizvoda, kategorije, pretrage, proizvođača,
  povezanih proizvoda, quickviewa, liste želja i košarice;
- da se sidrena cijena prikazuje informativno i ne mijenja iznos narudžbe;
- ručno uređivanje, obvezan razlog, `pending`/`confirmed` status i audit trag;
- uspješan i odbijen CSV import bez djelomične tihe pogreške;
- jedan dnevni `WEB` CSV, ispravan broj redaka i SHA-256;
- da CSV i XML za istu publikaciju imaju iste proizvode i vrijednosti;
- javnu stranicu arhive i najnovije javne CSV/XML URL-ove bez admin prijave;
- da publikacije starije od 30 dana nisu javno dostupne;
- 403 za cron bez ključa ili s pogrešnim ključem.

## Kontrolirani rollback

1. Isključiti dnevni cron.
2. U administraciji deaktivirati/deinstalirati modul.
3. Vratiti prethodne aplikacijske datoteke iz provjerene kopije/releasea.
4. Osvježiti OpenCart modifications i očistiti cache.
5. Provjeriti katalog, košaricu i checkout.

Rollback skripta ne briše tablice, sidrene cijene, audit ni arhivu. Takvo brisanje
nije dio hitnog rollbacka i smije se raditi samo zasebnom, izričito odobrenom
procedurom nakon što isteknu poslovne i zakonske potrebe za čuvanjem podataka.
