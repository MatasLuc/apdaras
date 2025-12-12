# Architektūra

Projektas naudoja Node.js/Express API, MySQL duomenų bazę ir numatytą JWT autentifikaciją. Front-end dar neįgyvendintas, tačiau API suformuota taip, kad galėtų aptarnauti tiek viešą parduotuvės dalį, tiek administratoriaus pultą.

## Pagrindiniai moduliai
- **Autentifikacija**: registracija ir prisijungimas su bcrypt ir JWT. Numatyta vartotojo rolė (`customer`, `admin`).
- **Kategorijos**: kategorijų ir subkategorijų medis.
- **Produktai**: produktų kūrimas, redagavimas, trynimas, kelių nuotraukų priskyrimas, pagrindinės nuotraukos pažymėjimas.
- **Kuponai**: nuolaidų kodai su galiojimo ir panaudojimų limitu.
- **Pristatymas**: pristatymo būdai su kaina ir preliminariu terminu.
- **Krepšelis**: vartotojo krepšelio įrašai su kiekių valdymu.

## Saugumas
- Visi administraciniai veiksmai naudoja `requireAuth` vidinę autentifikacijos tarpiklį.
- JWT pasirašomas `JWT_SECRET` raktu ir galioja 7 dienas.

## Diegimo pastabos
- Serverio paleidimo metu, jei duomenų bazė ir lentelės dar nesukurtos, jos sukuriamos automatiškai pagal schemą kode.
- Nuotraukų įkėlimas šiuo metu tik per URL lauką – vėliau galima pridėti tikrą failų saugyklą.
