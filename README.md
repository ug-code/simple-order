
## Simpler order rest api Laravel

![image](https://user-images.githubusercontent.com/17679067/145486517-e03d8f1d-5742-4247-800b-2b7617677cc4.png)
## Installation

without docker

create db "simple-ecommerce"
```shell
composer install && php artisan migrate:fresh --seed && php artisan serve
```

with docker
```shell
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v $(pwd):/var/www/html \
    -w /var/www/html \
    laravelsail/php80-composer:latest \
    composer install --ignore-platform-reqs
    
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate:fresh --seed
```


docker sail doc.

https://medium.com/@achalaarunalu/setting-up-an-existing-laravel-8-sail-docker-project-on-windows-wsl2-and-ubuntu-20-04-f0def4210258




## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
