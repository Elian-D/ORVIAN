#!/bin/bash

# Espera a que la app esté lista
sleep 5

# Queue worker en background (procesa jobs de Redis)
php /app/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 &

# Scheduler en background (corre cada minuto y ejecuta los comandos programados)
while true; do
    php /app/artisan schedule:run --no-interaction
    sleep 60
done &

# Mantiene el script vivo
wait