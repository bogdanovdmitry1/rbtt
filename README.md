Запуск проекта локально
==================================
Запустить docker контейнер
```bash
$ docker-compose up -d
```

ssh к контейнеру
```bash
$ docker exec -it docker-symfony4-php-fpm bash
```

БД и загрузка фикстур
```bash
$ php bin/console doctrine:database:create
$ php bin/console doctrine:schema:update --force
$ php bin/console doctrine:migrations:migrate
$ php bin/console doctrine:fixtures:load
# загрузка администратора в БД
```

проект доступен по адресу
```bash
http://localhost:8000
# или для windows:
http://192.168.99.100:8000 
```