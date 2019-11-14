# ozone-logs-azure
Projecto para descargar logs de azure

Instalacion:

    1.- Clonar el repositorio
    2.- Solicitar el archivo de credenciales y colocarlo en la raiz del projecto
    3.- En la raiz del projecto ejecutar "composer install"

Ejecucion del script:

El script tiene 4 argumentos TODOS OBLIGATORIOS

    1.- Aplicacion: puede ser "ozone|ocp"
    2.- Fecha: fecha para la busqueda de logs (yyyy-mm-dd)
    3.- StartTime: Hora de inicio de los logs (HH:MM)
    4.- EndTime: Hora de Fin de los logs (HH:MM)

Ejemplo de ejecucion

    php main.php ozone 2019-11-12 00:00 01:00
    Este ejemplo busca todos los logs en todos los frontales para el dia 12 de noviembre entre las 00:00 y la 01:00 