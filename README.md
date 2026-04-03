# callsTry1

Laravel 13 application with Inertia.js v3 + Vue 3 + Tailwind CSS v4.

## Stack

| Package | Version |
|---|---|
| PHP | ^8.3 |
| Laravel | ^13.0 |
| Inertia Laravel | ^3.0 |
| Laravel Fortify | ^1.34 |
| Laravel Wayfinder | ^0.1 |
| Vue | ^3.5 |
| Inertia Vue 3 | ^3.0 |
| Tailwind CSS | ^4.1 |
| Pest | ^4.4 |

---

## Instalacion local

### Requisitos

- PHP 8.3+
- Composer
- Node.js (LTS recomendado)
- npm

### Pasos

```bash
# 1. Clonar el repositorio
git clone <repo-url>
cd callsTry1

# 2. Instalar dependencias PHP
composer install

# 3. Copiar variables de entorno y generar clave
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env y correr migraciones
php artisan migrate

# 5. Instalar dependencias JS y compilar assets
npm install
npm run build

# 6. Levantar el servidor
composer run dev
```

> **Tip:** `composer run setup` ejecuta los pasos 2-5 de forma automatica.

### Desarrollo

```bash
composer run dev
```

Levanta en paralelo: servidor PHP, queue worker, log watcher (Pail) y Vite HMR.

### Tests

```bash
php artisan test --compact
```

---

> **Nota:** Este proyecto usa Laravel 13. Si actualizas dependencias, verifica compatibilidad con PHP 8.3 y revisa los changelogs de Inertia v3 y Wayfinder antes de hacer `composer update` o `npm update`.
