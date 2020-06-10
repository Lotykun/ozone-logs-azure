# ozone-logs-azure
Projecto para descargar logs de azure

Instalacion:

    1.- Clonar el repositorio
    2.- Solicitar el archivo de credenciales y colocarlo en la raiz del projecto
    3.- En la raiz del projecto ejecutar "composer install"

Ejecucion del script:

El script tiene 4 argumentos

    1.- Aplicacion: puede ser "ozone|ocp" (OBLIGATORIO)
    2.- Fecha inicio: Fecha Inicial para la busqueda de logs (yyyy-mm-dd H:i:s) (OBLIGATORIO)
    3.- Fecha Final: Fecha Final para la busqueda de logs (yyyy-mm-dd H:i:s) (OBLIGATORIO)
    4.- LogName: Dependiendo de la aplicacion (ocp | ozone) (OPCIONAL) puede ser 
        ("prod.api.log.1","prod.backend.log.1","prod.frontend.log.1","prod.log.1","prod.payment.log.1")

Ejemplo de ejecucion

    php main.php ozone "2020-05-24 18:15" "2020-05-24 19:15" prod.log.1
    Este ejemplo busca todos los logs del canal Prod.Log en todos los frontales para el dia 24 de Mayo
    entre las 18:15 y las 19:15
    
    php main.php ozone "2020-05-24 18:15" "2020-05-24 19:15"
    Este ejemplo busca todos los logs DE TODOS LOS CANALES en todos los frontales para el dia 24 de Mayo
    entre las 18:15 y las 19:15 