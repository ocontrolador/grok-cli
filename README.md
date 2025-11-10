# Grok CLI v0.2  
**Inteligência Artificial para PHP — Segurança, Refatoração, Testes e Versionamento Automatizado**

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Grok](https://img.shields.io/badge/Grok-4--fast--non--reasoning-000000?logo=xai&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-blue.svg)
![Ubuntu](https://img.shields.io/badge/Ubuntu-20.04-E95420?logo=ubuntu&logoColor=white)

> **"Máxima verdade. Máxima segurança. Máxima qualidade em PHP."**  
>  **Nota**: Não vai ter continuidade. Foi desenvolvida apenas para testar


---

## Status: **FUNCIONANDO**

```
grok-cli analyze test-grok.php --apply
```


- Análise de segurança (OWASP)
- Correção automática com `--apply`
- Backup automático
- Commit no Git
- Custo estimado por token
- Geração de testes, docs, código
- Estrutura `bin/` + Composer

---

## Instalação (Ubuntu 20.04)

```bash
# 1. Instala PHP 8.3
sudo apt update
sudo apt install php8.3 php8.3-cli php8.3-mbstring php8.3-xml php8.3-curl -y

# 2. Instala Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm composer-setup.php

# 3. Clona o projeto
git clone https://github.com/SantosDiaX/grok-cli.git
cd grok-cli

# 4. Instala dependências
composer install --no-dev

# 5. Configura .env
cp .env.example .env
nano .env
```

### `.env` (exemplo)

```env
GROK_API_KEY=sk-...
GROK_MODEL=grok-4-fast-non-reasoning
```

```bash
# 6. Torna executável
chmod +x bin/grok-cli.php

# 7. Cria comando global
sudo ln -sf $(pwd)/bin/grok-cli.php /usr/local/bin/grok-cli
```

---

## Uso (Comandos)

```bash
# Análise com segurança
grok-cli analyze src/User.php --security

# Análise + correção automática
grok-cli analyze src/ --security --apply

# Refatoração com instrução
grok-cli refactor src/ --apply --backup "Adicione PHPDoc e validação"

# Refatoração com segurança
grok-cli refactor app/ --apply -s "Proteja contra SQLi e XSS"

# Gerar código
grok-cli generate "Crie um Repository com Eloquent para User" -o src/Repositories/UserRepository.php

# Gerar testes
grok-cli test src/Services/PaymentService.php

# Gerar documentação
grok-cli doc src/ --format=markdown
```

---

## Comandos Disponíveis

| Comando | Descrição |
|-------|----------|
| `analyze` | Analisa segurança, bugs, sintaxe, PSR-12 |
| `refactor` | Refatora com instrução personalizada |
| `generate` | Gera código PHP a partir de texto |
| `test` | Gera testes PHPUnit com 100% cobertura |
| `doc` | Gera PHPDoc ou Markdown |
| `--apply` | Aplica mudanças automaticamente |
| `--backup` | Cria backup antes de modificar |
| `--security` | Ativa OWASP Top 10 |

---

## Custo Estimado (por milhão de tokens)

| Tipo | Preço |
|------|-------|
| Entrada | **$0.20** |
| Saída   | **$0.50** |

> Log: `logs/grok_usage_YYYY-MM-DD.log`

---

## Estrutura do Projeto

```
grok-cli/
├── bin/
│   └── grok-cli.php          ← Executável
├── vendor/                   ← Composer
├── src/                      ← (opcional) suas classes
├── logs/                     ← Uso e custo
├── .env                      ← API Key
├── composer.json
├── README.md                 ← Este arquivo
├── LICENSE                   ← MIT
└── .git/                     ← Git local (auto-iniciado)
```

---

## Segurança (OWASP Top 10)

- XSS → `htmlspecialchars()`
- SQLi → `prepared statements`
- RCE → Validação de entrada
- CSRF → Tokens
- Path Traversal → `realpath()`
- Injeção de comando → Escapamento

---

## Exemplo Prático

```bash
echo '<?php echo $_GET["name"]; ?>' > test-grok.php

grok-cli analyze test-grok.php --apply
```

**Saída:**
```
[ANÁLISE] test-grok.php
Relatório:
- XSS detectado em $_GET['name']
- Código corrigido:
```php
<?php
echo htmlspecialchars($_GET['name'] ?? '', ENT_QUOTES, 'UTF-8');
```

Correções aplicadas → backup: test-grok.php.bak.202511101931
Custo estimado: $0.000012
```

---

## Contribuição

1. Fork
2. `git checkout -b feature/nova`
3. `git commit -m "feat: X"`
4. Push + Pull Request

---

## Autor

**André Dias**  
**@SantosDiaX**  
**Brasil**  
**10 de novembro de 2025, 19:31 -03**

> *Construído com PHP-CLI puro, Grok-4, Git e verdade máxima.*

---

**Grok CLI — Porque seu código merece IA de verdade.**
```

---



