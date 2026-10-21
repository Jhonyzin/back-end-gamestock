# GameStock - Back-end

API da loja feita em PHP 8 com POO, MVC, DAO e PDO. Banco PostgreSQL no Render.

## Pastas
- `index.php` - entrada da API
- `routes/api.php` - rotas
- `controllers/` - recebem as requisições
- `models/` - classes dos dados (Usuario, Jogo, Pedido...)
- `dao/` - SQL do banco
- `core/` - Router, Request, Response, Auth, Upload, Validador, Cartao
- `config/Bancodados.php` - conexão com o banco

## Render
1. New > Web Service > repositório `back-end-gamestock`
2. Language: **Docker** | Root Directory: **vazio** | Dockerfile Path: `./Dockerfile`
3. Mesma região do banco
4. Environment:
   - `DATABASE_URL` = Internal Database URL do postgres
   - `FRONTEND_URL` = endereço do front
   - `APP_DEBUG` = `false`

Teste: `https://SEU-BACKEND.onrender.com/api` e `/api/jogos`

## Usuários de teste
- admin@gamestock.local / Admin@123
- vendedor@gamestock.local / Vendedor@123
- jogador@gamestock.local / Jogador@123

Obs: no plano grátis do Render as imagens enviadas pelo site somem quando faz deploy de novo.
