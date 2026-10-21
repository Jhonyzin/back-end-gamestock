# GameStock — Back-end no Render (PHP 8 + Docker + PostgreSQL)

API REST em PHP puro (POO + MVC + DAO + PDO). O Render não roda PHP nativamente, então este back-end sobe como **Web Service com Docker**, usando o `Dockerfile` desta pasta (`php:8.3-apache` com `pdo_pgsql`).

O banco é o **PostgreSQL do Render** (o mesmo que você administra pelo pgAdmin). O código também funciona com MySQL no XAMPP: quem escolhe é a variável `DB_DRIVER`.

## 1. Criar as tabelas (pgAdmin)
1. No Render, abra o seu PostgreSQL → **Connections** e copie: Hostname (externo), Port, Database, Username e Password.
2. No pgAdmin: **Register → Server** → aba *Connection* com esses dados; aba *Parameters/SSL* → **SSL mode = require**.
3. Abra o banco → **Query Tool** e rode, nesta ordem, os arquivos do repositório/pasta `data-base`:
   `01_estrutura.sql` → `02_dados_iniciais.sql` → `03_dados_demo.sql` (o `00` não é necessário no Render).

## 2. Criar o Web Service
1. No Render: **New → Web Service**.
   - Se o repositório não aparecer na lista, use a aba **Public Git Repository** e cole `https://github.com/Jhonyzin/back-end-gamestock`.
2. Configure:
   - **Language:** `Docker`
   - **Branch:** `main`
   - **Region:** a **mesma** do seu PostgreSQL (para usar a URL interna)
   - **Root Directory:** deixe **vazio** (o `Dockerfile` está na raiz deste repositório)
   - **Dockerfile Path:** `./Dockerfile`
   - **Instance Type:** Free
3. Em **Environment Variables**, cadastre:

| Variável | Valor |
|---|---|
| `DATABASE_URL` | a **Internal Database URL** do seu PostgreSQL (Render → banco → Connections) |
| `FRONTEND_URL` | endereço do front, ex.: `https://front-end-gamestock.onrender.com` |
| `APP_DEBUG` | `true` no primeiro teste (mostra o erro detalhado); depois troque para `false` |

   Com `DATABASE_URL` preenchida não é preciso `DB_HOST`, `DB_USER` etc. O Render define `PORT` sozinho.
4. **Create Web Service** e espere o deploy terminar (aba **Logs**).

## 3. Testar
- `https://SEU-BACKEND.onrender.com/api` → `{"sucesso":true,"dados":{"api":"GameStock 2025","status":"online",...}}`
- `https://SEU-BACKEND.onrender.com/api/jogos` → lista de jogos (se der erro de banco, confira a `DATABASE_URL`).
- Depois, no Static Site do front: `VITE_API_URL=https://SEU-BACKEND.onrender.com/api` e **Manual Deploy → Clear build cache & deploy**.

## Avisos
- **Plano gratuito:** o serviço "dorme" sem uso; o primeiro acesso pode levar ~1 minuto.
- **Disco efêmero:** imagens enviadas pelo site (pasta `uploads/`) somem a cada novo deploy. Para imagens permanentes, use **URL externa** no painel admin ou coloque as capas no front (`public/capas/{id}.jpg`).
- Senhas de teste (`Admin@123`, `Jogador@123`): troque antes de divulgar o site.
