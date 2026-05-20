#!/bin/bash

# Φορτώνει όλα τα SQL αρχεία στη βάση hospitaldb
# Χρησιμοποιήθηκε για MAMP σε Mac

MYSQL="/Applications/MAMP/Library/bin/mysql"

echo "Δημιουργία βάσης και πινάκων..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot < sql/install/01_hospitaldb9_schema.sql

echo "Φόρτωση load9a..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/load_parts/load9a.sql

echo "Φόρτωση load9b..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/load_parts/load9b.sql

echo "Φόρτωση load9c..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/load_parts/load9c.sql

echo "Φόρτωση load9d..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/load_parts/load9d.sql

echo "Φόρτωση load9e..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/load_parts/load9e.sql

echo "Φόρτωση triggers..."
$MYSQL -h 127.0.0.1 -P 8889 -u root -proot hospitaldb < sql/install/02_triggers_after_load.sql


echo "Η φόρτωση ολοκληρώθηκε."
echo "Άνοιξε την εφαρμογή στο: http://localhost:8888/hospital_app/"