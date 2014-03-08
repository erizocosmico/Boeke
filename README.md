#Boeke

Boeke es una aplicación para gestionar los préstamos de libros a alumnos.

### Características
* Gestión de usuarios (y usuarios administradores).
* Gestión de libros.
* Gestión de ejemplares de cada libro.
* Gestión de alumnos.
* Gestión de niveles.
* Gestión de asignaturas.

Boeke permite filtrar los diferentes ejemplares para poder acceder rápidamente a aquellos que se desean consultar. Se ha utilizado una organización con especial cuidado en la usabilidad y la facilidad de uso de la aplicación.

### Instalación
Para instalar la aplicación es necesario clonar el repositorio de github, descargar los paquetes necesarios para que funcione y ejecutar el instalador.

```
git clone https://github.com/mvader/Boeke
cd Boeke
composer.phar install
bower install
```
**Nota:** Requiere composer y bower instalados si se va a instalar de este método.

Si no deseas tener que hacer todo este proceso o tu servidor no puede utilizar estos gestores de paquetes puedes descargar la aplicación con todos los paquetes necesarios desde [aquí](https://github.com/mvader/Boeke/releases/download/1.0.1/Boeke-1.0.1.zip).

Una vez subida la aplicación al servidor, de cualquiera de las dos maneras, habrá que ir al instalador:

```
http://tudominio.com/install.php
```

###Instalar en un subdirectorio
Si el ```DocumentRoot``` de Apache no apunta a la carpeta ```public```, será necesario añadir ```/public/``` a la ruta. Es decir, si tienes la aplicación en ```http://midominio.com/boeke/``` para acceder será necesario acceder a ```http://midominio.com/boeke/public/```.

Para que funcione correctamente será necesario hacer los siguientes cambios:

* Crear un fichero ```.htaccess``` con el siguiente contenido en la carpeta de la aplicación:
```
RewriteEngine On
RewriteBase /ruta-de-nuestro-subdirectorio/
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^ public/ [L,R=301]
````
* Modificar el fichero ```.htaccess``` de la carpeta ```public```:
```
RewriteEngine On
RewriteBase /ruta-de-nuestro-subdirectorio/public/
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]
```