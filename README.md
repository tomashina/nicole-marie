# Nicole Marie

OpenCart 3.0.3.8 application source for the Nicole Marie webshop.

Production database credentials, runtime storage, logs, generated caches and
customer-uploaded product images are intentionally excluded from Git.

## Local setup

Create `config.php` and `admin/config.php` from the OpenCart distribution
templates and provide environment-specific URLs, paths and database settings.

## Sidrene cijene i digitalni cjenik

Projekt uključuje OpenCart modul za sidrene cijene, masovni CSV unos te dnevni
digitalni cjenik u CSV i XML formatu. Produkcijski postupak, provjere, cron i
kontrolirani rollback opisani su u
[`docs/sidrene_cijene_pustanje_na_produkciju.md`](docs/sidrene_cijene_pustanje_na_produkciju.md).
