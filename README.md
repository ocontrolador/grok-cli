# Grok CLI v2.0  
**Inteligência Artificial para PHP — Análise, Segurança, Refatoração e Versionamento Automatizado**

![PHP](https://img.shields.io/badge/PHP-8.3%2B-777BB4?logo=php&logoColor=white)
![Grok](https://img.shields.io/badge/Grok-4--fast--non--reasoning-000000?logo=xai&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-blue.svg)
![Ubuntu](https://img.shields.io/badge/Ubuntu-20.04-E95420?logo=ubuntu&logoColor=white)

> **"Máxima verdade. Máxima segurança. Máxima qualidade em PHP."**  
> — @SantosDiaX | André

---

## Visão Geral

O **Grok CLI** é uma ferramenta de linha de comando em **PHP puro** que utiliza o modelo **Grok-4-fast-non-reasoning** da xAI para:

- Analisar código PHP (arquivo ou pasta)
- Detectar **vulnerabilidades de segurança** (OWASP Top 10)
- Corrigir **bugs de sintaxe, lógica e estrutura**
- **Refatorar automaticamente** com PSR-12, SOLID e tipagem estrita
- Gerar **testes unitários (PHPUnit)**, **documentação (PHPDoc/Markdown)**
- **Aplicar mudanças com backup**
- **Versionar tudo com Git**
- **Calcular custo estimado por uso**

---

## Recursos Principais

| Recurso | Descrição |
|-------|----------|
| `analyze` | Análise funcional, estrutural e de segurança |
| `refactor` | Refatoração com instrução personalizada |
| `test` | Gera testes PHPUnit com 100% de cobertura |
| `doc` | Gera PHPDoc ou Markdown |
| `generate` | Cria código PHP a partir de instrução |
| `--apply` | Aplica mudanças automaticamente |
| `--backup` | Cria backup antes de modificar |
| `--security` | Ativa análise OWASP completa |
| Git Integration | `git init`, `README.md`, `LICENSE`, commits automáticos |

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
