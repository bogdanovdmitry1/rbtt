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

БД: миграции и фикстуры
```bash
$ php bin/console doctrine:migrations:migrate
$ php bin/console doctrine:fixtures:load
# загрузка администратора в БД
# username: admin@localhost.by
# password: adminpwd
```

проект доступен по адресу
```bash
http://localhost:8000
# или для windows:
http://192.168.99.100:8000 
```


### Swagger
страница с документацией
```bash
/api-doc
```
для корректной работы авторизации в Swagger необходимо перед токеном вставить "Bearer " в форме Authorize