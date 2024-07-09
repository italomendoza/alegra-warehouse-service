# Warehouse Microservice

## Descripción
El microservicio `warehouse` se encarga de gestionar el inventario y las órdenes de compra de ingredientes. Interactúa con RabbitMQ para enviar y recibir mensajes relacionados con la disponibilidad de ingredientes y la reposición del inventario.

## Diagrama de arquitectura en aws
- https://lucid.app/lucidchart/966c3239-3d04-4339-a634-1e8744124fde/edit?viewport_loc=-1560%2C-734%2C2781%2C1558%2C0_0&invitationId=inv_a8695cc3-978c-401c-952a-07a3a12262ac

## Requisitos Previos
- Docker
- Docker Compose
- PHP 8.2 o superior
- Composer
- RabbitMQ

## CONTRUCCION DEL SISTEMA
- crea una carpeta general y dentro de ella clona los 3 repositorios, 2 backends de microservicios (kitchen y warehouse) y 1 del frontend con vue js.
- luego usa este composer:
## Docker Compose Configuration

```yaml
version: '3.8'

services:

  kitchen-service:
    build:
      context: ./kitchen-service
      dockerfile: Dockerfile
    container_name: kitchen_service
    ports:
      - "9002:80"
    volumes:
      - ./kitchen-service:/var/www/html
    networks:
      - app_network
    depends_on:
      - kitchen-db
      - rabbitmq
    environment:
      - APP_ENV=local
      - DB_CONNECTION=mysql
      - DB_HOST=kitchen-db
      - DB_PORT=3306
      - DB_DATABASE=kitchen_db
      - DB_USERNAME=user_kitchen_db
      - DB_PASSWORD=user_kitchen_password
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672

  kitchen-db:
    image: mariadb:10.6
    container_name: kitchen_db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: kitchen_db
      MYSQL_USER: user_kitchen_db
      MYSQL_PASSWORD: user_kitchen_password
    volumes:
      - kitchen_db_data:/var/lib/mysql
    networks:
      - app_network
    ports:
      - 3307:3306

  warehouse-service:
    build:
      context: ./warehouse-service
      dockerfile: Dockerfile
    container_name: warehouse_service
    ports:
      - "9003:80"
    volumes:
      - ./warehouse-service:/var/www/html
    networks:
      - app_network
    depends_on: 
      - warehouse-db
      - rabbitmq
    environment:
      - APP_ENV=local
      - DB_CONNECTION=mysql
      - DB_HOST=warehouse-db
      - DB_PORT=3306
      - DB_DATABASE=warehouse_db
      - DB_USERNAME=warehouse_user
      - DB_PASSWORD=user_password
      - RABBITMQ_HOST=rabbitmq
      - RABBITMQ_PORT=5672

  warehouse-db:
    image: mariadb:10.6
    container_name: warehouse_db
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: warehouse_db
      MYSQL_USER: warehouse_user
      MYSQL_PASSWORD: user_password
    volumes:
      - warehouse_db_data:/var/lib/mysql
    networks:
      - app_network
    ports:
      - 3308:3306
  
  rabbitmq:
    image: rabbitmq:3-management
    container_name: rabbitmq
    ports:
      - "5672:5672"
      - "15672:15672"
    networks:
      - app_network
    environment:
      - RABBITMQ_DEFAULT_USER=user
      - RABBITMQ_DEFAULT_PASS=password
      - RABBITMQ_DEFAULT_VHOST=/

networks:
  app_network:
    driver: bridge

volumes:
  kitchen_db_data:
  warehouse_db_data:
```

## Configuración del Entorno
1. Copia el archivo `.env.example` a `.env`:
    ```bash
    cp .env.example .env
    ```
2. Configura las variables de entorno en el archivo `.env` según sea necesario:
    ```dotenv
    APP_NAME=Warehouse
    APP_ENV=local
    APP_KEY=base64:...
    APP_DEBUG=true
    APP_URL=http://localhost

    RABBITMQ_HOST=rabbitmq
    RABBITMQ_PORT=5672
    RABBITMQ_USER=user
    RABBITMQ_PASSWORD=password
    RABBITMQ_VHOST=/
    ```

## Instalación
1. Instala las dependencias de Composer:
    ```bash
    composer install
    ```
2. Genera la clave de la aplicación:
    ```bash
    php artisan key:generate
    ```

## Uso de Docker
1. Construye y levanta los contenedores:
    ```bash
    docker-compose up -d --build
    ```
2. Aplica las migraciones de la base de datos:
    ```bash
    docker-compose exec app php artisan migrate
    ```

## Comandos Útiles
- Ejecutar las migraciones:
    ```bash
    php artisan migrate
    ```
## run seeders

php artisan db:seed --class=WarehouseIngredientsSeeder

- Consumir mensajes de RabbitMQ:
    ```bash
    php artisan rabbitmq:consume
    ```

## Ejecución de Pruebas
1. Ejecuta las pruebas con PHPUnit:
    ```bash
    php artisan test
    ```

## Contribuir
Por favor, abre un issue o una solicitud de extracción para contribuir a este proyecto.

## Licencia
Este proyecto está licenciado bajo la Licencia MIT.
