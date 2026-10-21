# GameStock — Back-end no Render (PHP 8 + Docker)

API REST em PHP puro (POO + MVC + DAO + PDO). O Render não roda PHP nativamente, então este back-end sobe como **Web Service com Docker** usando o `Dockerfile` desta pasta (`php:8.3-apache`).

## Publicar
1. Suba esta pasta para um repositório no GitHub (pode ser o repositório inteiro do projeto).
2. No Render: **New → Web Service** → escolha o repositório.
   - **Language / Runtime:** Docker
   - **Root Directory:** `back-end` (se o repositório tiver as três pastas)
   - **Dockerfile Path:** `./Dockerfile`
3. Em **Environment → Environment Variables**, cadastre (veja `.env.example`):

| Variável | Exemplo | Observação |
|---|---|---|
| `DB_HOST` | `meu-mysql.provedor.com` | MySQL **externo** (o Render não tem MySQL gerenciado) |
| `DB_PORT` | `3306` | |
| `DB_NAME` | `gamestock` | |
| `DB_USER` / `DB_PASS` | — | credenciais do seu MySQL |
| `DB_SSL` | `true` ou `false` | alguns provedores exigem SSL |
| `FRONTEND_URL` | `https://gamestock.onrender.com` | endereço do Static Site (CORS) |
| `APP_DEBUG` | `false` | |

   O Render já define `PORT` (o Apache usa essa porta automaticamente).
4. Depois do deploy, teste: `https://SEU-BACKEND.onrender.com/api` (status) e `.../api/jogos`.
5. Crie as tabelas rodando os arquivos da pasta `data-base/` no seu MySQL (ver o README de lá).

## ⚠️ Disco efêmero
O disco do plano gratuito do Render é **apagado a cada novo deploy**: fotos de perfil e imagens de jogos **enviadas pelo site** (pasta `uploads/`) somem. Para imagens permanentes, use **URL externa** (`https://...`) no painel admin, ou coloque as capas no front-end (`front-end/public/capas/{id}.jpg`).

## Estrutura
Igual à versão XAMPP: `index.php` (front controller), `routes/api.php`, `controllers/`, `models/`, `dao/`, `core/`, `config/Bancodados.php`. Detalhes de segurança e do formato das respostas: veja `DECISOES.md` e o README principal do projeto.
