# Apdaras.lt

Internetinės parduotuvės projektas, skirtas prekiauti marškinėliais, džemperiais ir aksesuarais. Reikalavimai: vartotojų autentifikacija, produktų valdymas su keliomis nuotraukomis, kategorijos ir subkategorijos, nuolaidų kuponai, pristatymo valdymas ir krepšelis. Visa informacija laikoma MySQL duomenų bazėje.

## Struktūra
- `backend/` – Node.js/Express API su MySQL.
- `docs/` – architektūros ir API planas.

## Greitas paleidimas
1. Sukurkite `.env` pagal `backend/.env.example` ir nurodykite MySQL duomenų bazės parametrus.
2. Paleiskite priklausomybes:
   ```bash
   cd backend
   npm install
   npm start
   ```
   Paleidimo metu serveris automatiškai sukurs nurodytą duomenų bazę ir lenteles, jei jų nėra.
3. API pasiekiama `http://localhost:4000`. Numatyti maršrutai aprašyti `docs/api.md`.

## Tolimesni žingsniai
- Pridėti front-end (pvz., Next.js) su parduotuvės ir administratoriaus pultu UI.
- Įgyvendinti failų įkėlimą nuotraukoms (pvz., `multer` + S3 ar lokali saugykla).
- Įdiegti integraciją su mokėjimų teikėju.
