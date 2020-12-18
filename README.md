### Запуск (перед запуском лучше взглянуть на скрипт)
```bash
./build.sh
```

### Можно пользоваться следующими API:

Если будут ошибки связанные с sql, то нужно выполнить миграции вручную
```bash
docker exec -it $(docker ps -a | grep php-fpm$ | awk '{print $1}') bin/console doctrine:migrations:migrate --no-interaction
```

https://documenter.getpostman.com/view/11271065/TVsuBSae - API Авторов

https://documenter.getpostman.com/view/11271065/TVsuBSag - API Книг

**Поиск регистрозависимый (можно было бы добавить lower например).**

В поиске зашита выдача только первых 20 книг

В поиске можно бы сделать пагинацию и например какие-то дополнительные параметры фильтрации (например по авторам).

Можно было воспользоваться полнотекстовым поиском

### Наполнение базы 10 000 данных по авторам и книгам
```bash
docker exec -it $(docker ps -a | grep php-fpm$ | awk '{print $1}') bin/console doctrine:fixtures:load --no-interaction
```

Можно было использовать faker

### Запуск тестов
```bash
docker exec -it $(docker ps -a | grep php-fpm$ | awk '{print $1}') vendor/bin/phpunit
```

### Дополнительно

- запускать только в dev режиме, для prod не настраивал
- возможно будут проблемы с composer и например github попросит ключ, так как скачивается много библиотек (можно было бы прописать ключик при сборке)
- Для очистки docker-а можно использовать (делать этого по идее не нужно!):
```bash
docker stop $(docker ps -a -q) && \
docker rm $(docker ps -a -q) && \
docker rmi $(docker images -q) --force && \
docker volume rm $(docker volume ls -qf dangling=true)
```   