# Proyecto Fin de Ciclo Desarrollo de Apliciones Web

## 1. Portada

<div align="center">

<img src="img/logo.png" alt="Logo TakeOne" width="180"/>

# TakeOne

### Proyecto Final del Grado Superior de Desarrollo de Aplicaciones Web (DAW).

---

**Centro educativo:** IES Suárez de Figueroa (Zafra).

**Autora:** María Martín Vélez.

**Tutor:** José Andrés Paredes Arribas.

**Fecha de presentación:** Junio 2026.

**Repositorio:** [github.com/suarezfigueroa/2025-2026_MariaMartin](https://github.com/suarezfigueroa/2025-2026_MariaMartin)

</div>



## 2. Índice

1. [Portada](#1-portada)
2. [Índice](#2-índice)
3. [Introducción](#3-introducción)
4. [Objetivos del proyecto](#4-objetivos-del-proyecto)
5. [Justificación del proyecto](#5-justificación-del-proyecto)
   - 5.1. [Análisis de mercado](#51-análisis-de-mercado)
   - 5.2. [Vinculación con contenidos del Ciclo Formativo](#52-vinculación-con-contenidos-del-ciclo-formativo)
6. [Recursos utilizados](#6-recursos-utilizados)
   - 6.1. [Entornos de desarrollo](#61-entornos-de-desarrollo)
   - 6.2. [Lenguajes de programación](#62-lenguajes-de-programación)
   - 6.3. [Utilidades](#63-utilidades)
7. [Tecnologías de desarrollo](#7-tecnologías-de-desarrollo)
8. [Diseño del proyecto](#8-diseño-del-proyecto)
   - 8.1. [Diseño de la base de datos](#81-diseño-de-la-base-de-datos)
     - 8.1.1. [Diagrama E/R](#811-diagrama-er)
     - 8.1.2. [Modelo Relacional](#812-modelo-relacional)
   - 8.2. [Carga de datos inicial](#82-carga-de-datos-inicial)
   - 8.3. [Diseño de la interfaz de usuario](#83-diseño-de-la-interfaz-de-usuario)
   - 8.4. [Roles de la aplicación](#84-roles-de-la-aplicación)
   - 8.5. [Usuarios de prueba](#85-usuarios-de-prueba)
9. [Lógica y codificación del proyecto](#9-lógica-y-codificación-del-proyecto)
   - 9.1. [Principales procesos](#91-principales-procesos)
   - 9.2. [Aspectos relevantes de la implementación](#92-aspectos-relevantes-de-la-implementación)
     - 9.2.1. [Validación de datos](#921-validación-de-datos)
     - 9.2.2. [Control de acceso](#922-control-de-acceso)
     - 9.2.3. [Sistema de carpetas](#923-sistema-de-carpetas)
10. [Despliegue web del proyecto](#10-despliegue-web-del-proyecto)
    - 10.1. [Requisitos hardware](#101-requisitos-hardware)
    - 10.2. [Servidores utilizados](#102-servidores-utilizados)
    - 10.3. [Seguridad](#103-seguridad)
    - 10.4. [Script de despliegue](#104-script-de-despliegue)
11. [Manual de usuario](#11-manual-de-usuario)
12. [Conclusiones y aspectos a mejorar](#12-conclusiones-y-aspectos-a-mejorar)
13. [Bibliografía](#13-bibliografía)
- [Anexos](#anexos)



## 3. Introducción

TakeOne es una plataforma web sobre cine nacida de una pasión y necesidad personal. Como fanática del cine siempre he disfrutado compartir las películas que veo, 
intercambiar opiniones y descubrir nuevas recomendaciones, así como ver muchas películas y nunca cansarme de ello. Sin embargo, tras explorar muchas páginas, ninguna terminaba de ofrecerme lo que realmente buscaba como usuaria.

De esa necesidad surgió TakeOne: la idea de reunir en un mismo sitio todo lo que 
una persona apasionada por el cine podría necesitar. Una plataforma que funciona 
a la vez como red social y como fuente de información cinematográfica, donde los 
usuarios pueden descubrir películas, crear listas, unirse a grupos de debate, 
seguir las últimas noticias del sector y recibir recomendaciones personalizadas.

Lo que diferencia a TakeOne de otras webs similares es el enfoque con el que ha 
sido construida. El estilo es muy personal y bien cuidado desde la perspectiva de una usuaria real, pensando en todo momento en la comodidad e intuición de quien la usa. Cada detalle ha sido cuidado con el objetivo de crear una experiencia agradable, personal y cercana, hecha con mucho cariño.



## 4. Objetivos del proyecto

El objetivo principal de TakeOne es ofrecer una plataforma completa que cubra las necesidades reales que a un usuario que le encante el cine pueda tener, 
reuniendo en un mismo sitio funcionalidades que habitualmente se encuentran dispersas 
en diferentes webs.

De forma más concreta, los objetivos del proyecto son:

- Ofrecer una herramienta útil para **descubrir, guardar y valorar películas**, 
  permitiendo al usuario llevar un registro personalizado de lo que ha visto, 
  lo que tiene pendiente y sus favoritas.
- Facilitar el **descubrimiento de nuevas películas** mediante filtros por género, 
  país, plataforma o año de estreno, así como un sistema de recomendaciones 
  personalizadas basado en los gustos del usuario o totalmente aleatoria.
- Crear un espacio de **comunidad cinematográfica** donde los usuarios puedan 
  compartir opiniones a través de comentarios, grupos de chat y listas públicas.
- Mantener al usuario **informado** con las últimas noticias del mundo del cine, 
  la cartelera actual y los próximos estrenos en España.
- Desarrollar una aplicación **cómoda, intuitiva y con una identidad visual 
  cuidada**, construida desde la perspectiva de una usuaria real y con muchas opciones para personalizar la cuenta.
- Aplicar y consolidar los conocimientos adquiridos a lo largo del Ciclo Formativo 
  de Desarrollo de Aplicaciones Web, desarrollando un proyecto completo de principio 
  a fin desde el diseño de la base de datos hasta el despliegue en producción.



## 5. Justificación del proyecto

### 5.1. Análisis de mercado

La motivación principal de TakeOne es básicamente personal. Como usuaria 
habitual de plataformas sobre cine, siempre he buscado una aplicación que combinase 
el estilo de diario personal —donde poder registrar y compartir lo que ves— con una 
vertiente social y comunitaria real. Al no encontrarla, decidí crearla y diseñarla a mi gusto.

Actualmente existen varias plataformas en el mercado con funcionalidades similares:

- **Letterboxd** — Red social centrada en el registro y valoración de películas. 
  Permite crear listas, seguir a otros usuarios y escribir reseñas. Es la más 
  parecida a TakeOne en cuanto a estilo, pero carece de una funcionalidad de 
  grupos de chat o comunidades temáticas donde debatir en tiempo real.
- **FilmAffinity** — Portal de cine muy completo a nivel informativo, con fichas 
  detalladas y valoraciones de usuarios. Sin embargo, su diseño es poco moderno 
  y no tiene una vertiente social desarrollada.
- **IMDb** — La base de datos cinematográfica más grande del mundo. Muy útil como 
  referencia, pero orientada principalmente a la consulta de información, sin 
  apenas funcionalidades sociales o de comunidad.
- **TMDB (The Movie Database)** — Base de datos colaborativa con API pública. 
  Muy completa a nivel técnico pero con una experiencia de usuario poco trabajada 
  a nivel visual y social.
- **Rotten Tomatoes** — Plataforma centrada en la crítica cinematográfica 
  profesional y del público. No dispone de funcionalidades de comunidad ni de 
  personalización para el usuario.
- **Fotogramas** — Revista digital española de cine, orientada principalmente 
  a noticias y críticas. No ofrece funcionalidades de registro personal ni 
  de comunidad.

TakeOne se diferencia de todas ellas al combinar en una sola plataforma el registro 
personal de películas, la personalización de la cuenta, las recomendaciones personalizadas, las noticias de actualidad y, sobre todo, una comunidad activa a través de grupos de chat 
temáticos; algo que ninguna de las alternativas actuales ofrece de forma integrada.

---

### 5.2. Vinculación con contenidos del Ciclo Formativo

TakeOne ha sido desarrollada aplicando los conocimientos adquiridos a lo largo del 
Ciclo Formativo de Desarrollo de Aplicaciones Web (DAW). Los módulos que mayor 
relación tienen con el proyecto son:

- **Bases de datos** — Diseño del modelo entidad-relación, creación del esquema 
  relacional y gestión de la base de datos MySQL que sustenta toda la aplicación.
- **Desarrollo Web en Entorno Cliente** — Implementación de la interfaz de usuario 
  con HTML, CSS y JavaScript, incluyendo efectos visuales, sliders, validaciones 
  en tiempo real y comunicación asíncrona con el servidor mediante fetch.
- **Desarrollo Web en Entorno Servidor** — Desarrollo de la lógica de negocio 
  con PHP, gestión de sesiones, control de acceso por roles y construcción de 
  las diferentes APIs internas de la aplicación.
- **Despliegue de Aplicaciones Web** — Configuración del entorno de producción, 
  gestión del servidor y publicación de la aplicación en un hosting real.
- **Lenguajes de Marcas** — Estructuración semántica del contenido mediante HTML5 
  y uso de formatos como JSON para el intercambio de datos con APIs externas.
- **Programación** — Aplicación de la lógica de programación estructurada y 
  orientada a objetos en el desarrollo del backend de la aplicación.



## 6. Recursos utilizados

### 6.1. Entornos de desarrollo

| Herramienta | Uso |
|-------------|-----|
| [Visual Studio Code](https://code.visualstudio.com/) | Editor de código principal, utilizado para todo el desarrollo frontend y backend del proyecto |
| [phpMyAdmin](https://www.phpmyadmin.net/) | Gestión visual de la base de datos MySQL: creación de tablas, consultas y administración del esquema |

---

### 6.2. Lenguajes de programación

**HTML5 y CSS3**
Utilizados para toda la parte visual de la aplicación. HTML5 estructura 
semánticamente el contenido de cada página, mientras que CSS3 se encarga 
del diseño, los efectos visuales, las animaciones y la adaptación responsive 
de la interfaz.

**PHP**
Lenguaje principal del backend. Se ha utilizado para:
- Operaciones CRUD sobre la base de datos.
- Actualización de estados de películas (pendientes, favoritas, vistas).
- Creación de endpoints para la comunicación con el frontend.
- Sistema de likes y valoraciones.
- Manejo y validación de formularios.
- Consultas dinámicas integradas con SQL.

**JavaScript**
Lenguaje principal del frontend interactivo. Se ha utilizado para:
- Manipulación del DOM y gestión de eventos.
- Filtros dinámicos de contenido.
- Chat en tiempo real.
- Modales dinámicos.
- Validación visual de los formularios de login y registro.
- Sliders interactivos.

**AJAX**
Utilizado como pieza fundamental para la comunicación asíncrona entre el 
frontend y el backend, permitiendo actualizar el contenido de la página sin 
necesidad de recargar. Sus usos principales han sido:
- Búsqueda instantánea de películas.
- Actualización del chat en tiempo real.
- Envío y edición de mensajes.
- Gestión de favoritos y valoraciones.
- Filtros dinámicos de contenido.
- Actualización de datos de usuario.
- Interacción con listas y grupos.

**SQL**
Utilizado para todas las operaciones sobre la base de datos:
- Consultas de búsqueda, inserción, actualización y eliminación de datos.
- Gestión de listas y favoritos.
- Sistema de chats y mensajes.
- Relaciones entre tablas mediante claves foráneas.
- Filtrado y recuperación de datos en tiempo real.
- Consultas optimizadas integradas en PHP para garantizar una comunicación 
  eficiente con el frontend.

---

### 6.3. Utilidades

| Recurso | Descripción | Enlace |
|---------|-------------|--------|
| **Bootstrap 5.3.2** | Framework CSS utilizado para la maquetación responsive y componentes de la interfaz | [getbootstrap.com](https://getbootstrap.com/) |
| **Font Awesome** | Librería de iconos vectoriales utilizada en botones, menús y elementos interactivos de la interfaz | [fontawesome.com](https://fontawesome.com/) |
| **Material Icons** | Iconos de Google utilizados como complemento visual en distintas secciones de la aplicación | [fonts.google.com/icons](https://fonts.google.com/icons) |
| **Google Fonts** | Fuentes tipográficas utilizadas: *Poppins* y *Playfair Display* | [fonts.google.com](https://fonts.google.com/) |
| **Lunacy** | Herramienta de diseño utilizada al inicio del proyecto para realizar el boceto inicial y definir la estructura visual | [icons8.com/lunacy](https://icons8.com/lunacy) |
| **Stitch** | Herramienta experimental de Google Labs utilizada para generar propuestas de interfaces de usuario mediante inteligencia artificial y lenguaje natural | [stitch.withgoogle.com](https://stitch.withgoogle.com/) |
| **TMDB** | Base de datos cinematográfica consultada como fuente de información para las películas | [themoviedb.org](https://www.themoviedb.org/) |



## 7. Tecnologías de desarrollo

TakeOne sigue una arquitectura **cliente-servidor clásica**, en la que el navegador 
del usuario actúa como cliente y se comunica con el servidor mediante endpoints PHP 
que devuelven datos en formato JSON. A continuación se describen las principales 
tecnologías utilizadas y los motivos de su elección.

---

### HTML5 y CSS3

HTML5 usado para la estructuración del contenido web. 

CSS3 se ha utilizado para el diseño de la interfaz, animaciones, efectos hover y la adaptación responsive de todas las páginas.

Los elegí por ser los estándares fundamentales del desarrollo web frontend, 
complementados con Bootstrap para agilizar la maquetación.

---

### PHP


En TakeOne actúa como capa de backend, gestionando todos los endpoints que recibe 
el frontend y ejecutando las operaciones necesarias sobre la base de datos.

Elegí PHP frente a otras alternativas como Node.js por ser el lenguaje con el que 
más experiencia tengo, lo que me ha permitido un desarrollo más ágil y sólido.

---

### JavaScript y AJAX

JavaScript es el lenguaje de programación del navegador, responsable de toda la 
interactividad de la interfaz. Permite manipular el DOM, gestionar eventos y 
comunicarse con el servidor de forma asíncrona.

En TakeOne, AJAX es una pieza fundamental, ya que gran parte de la experiencia de usuario —búsquedas, chats, filtros, valoraciones— se actualiza en tiempo real gracias a él.

Lo elegí frente a frameworks como React o Vue por tratarse de JavaScript puro, 
más adecuado para el alcance del proyecto y coherente con los conocimientos que he
adquirido en el ciclo.

---

### MySQL / MariaDB

La versión utilizada en el proyecto es **MariaDB 10.4.28**.

Uso SQL para almacenar toda la información de la aplicación: usuarios, películas, 
listas, comentarios, grupos, mensajes y noticias, entre otros.

Lo elegí frente a otras alternativas como PostgreSQL o MongoDB por su amplia 
compatibilidad con PHP, su integración nativa con XAMPP y por ser la base de datos 
trabajada durante el ciclo formativo.

---

### Bootstrap 5.3.2

Lo elegí frente a otras opciones como Tailwind CSS por su mayor facilidad de uso 
y por ser el framework trabajado durante el ciclo formativo.

---

### XAMPP

Ha sido utilizado como entorno de desarrollo local durante todo el proyecto, permitiendo simular un servidor real en la máquina de desarrollo antes del despliegue final. Además es el que más he usado durante el ciclo y con el que más manejo tengo.


## 8. Diseño del proyecto

### 8.1. Diseño de la base de datos

#### 8.1.1. Diagrama E/R

![Diagrama E/R](img/TakeOne Modelo E_R.drawio.png)

#### 8.1.2. Modelo Relacional

![Modelo Relacional](img/Modelo_relacional_TakeOne.jpeg)

La base de datos de TakeOne está compuesta por **26 tablas** que gestionan todas 
las entidades y relaciones de la aplicación. A continuación se describen las 
principales:

**Entidades principales:**

| Tabla | Descripción |
|-------|-------------|
| `usuarios` | Almacena los datos de todos los usuarios registrados: nombre, email, contraseña (cifrada), avatar, biografía, localidad, rol y estado |
| `peliculas` | Contiene toda la información de cada película: título, título original, año, duración, país, sinopsis, puntuación IMDb, póster, backdrop, tráiler, director, guionistas y productora |
| `generos` | Catálogo de géneros cinematográficos |
| `plataformas` | Catálogo de plataformas de streaming con su nombre y logo |
| `noticias` | Noticias publicadas en la plataforma, con título, descripción, contenido, imagen, autor y fecha |
| `grupos` | Grupos de comunidad con nombre, descripción, imagen, tipo (debates, recomendaciones, reseñas o club-cine) y género asociado |
| `listas` | Listas de películas creadas por los usuarios, con visibilidad configurable (pública, amigos o privada) |

**Tablas de relación y funcionalidad:**

| Tabla | Descripción |
|-------|-------------|
| `peliculas_generos` | Relación N:M entre películas y géneros |
| `peliculas_plataformas` | Relación N:M entre películas y plataformas de streaming |
| `listas_peliculas` | Relación N:M entre listas y películas |
| `listas_likes` | Registra los me gusta que los usuarios dan a las listas |
| `usuarios_peliculas` | Gestiona el estado de cada película para cada usuario (pendiente, favorita o vista) y su valoración |
| `usuarios_favoritas_perfil` | Almacena las 5 películas favoritas del perfil del usuario con su orden |
| `usuarios_generos_favoritos` | Relación N:M entre usuarios y sus géneros favoritos |
| `amistades` | Gestiona las solicitudes y relaciones de amistad entre usuarios (pendiente, aceptada o rechazada) |
| `grupos_usuarios` | Relación N:M entre grupos y usuarios miembros |
| `mensajes_grupo` | Mensajes enviados en los chats de grupo, con estado activo o borrado |
| `mensajes_privados` | Mensajes privados entre usuarios, con control de lectura y estado |
| `comentarios_peliculas` | Comentarios y reseñas de los usuarios sobre películas, con opción de marcar como spoiler |
| `moderacion_comentarios` | Registra los comentarios reportados por los usuarios con el motivo del reporte |
| `penalizaciones_grupo` | Gestiona los baneos de usuarios en grupos, con duración y motivo |
| `actividad_usuario` | Diario de actividad del usuario: películas vistas, comentarios, valoraciones, etc. Alimentado mediante triggers |
| `historial_sugerencias` | Historial de películas sugeridas al usuario en la sección Sugerir |
| `peliculas_en_cartelera` | Películas actualmente en cartelera en España |
| `proximos_estrenos` | Películas con fecha de estreno próxima |
| `recomendadas_semana` | Selección semanal de hasta 10 películas recomendadas por el administrador |
| `reparto` | Actores y personajes asociados a cada película |
| `contacto` | Formularios de contacto enviados por los usuarios |

---

### 8.2. Carga de datos inicial

El script SQL con los datos iniciales de la base de datos (películas, géneros, 
plataformas, usuarios de prueba, etc.) se encuentra en los anexos del proyecto:

📎 [Anexo I — Script SQL (DML)](#anexo-i--script-sql-dml)

---

### 8.3. Diseño de la interfaz de usuario

El diseño de TakeOne fue planificado desde el inicio con un estilo visual muy 
cuidado, pensado desde la perspectiva de una usuaria real. Antes de comenzar el 
desarrollo se realizó un boceto inicial con la herramienta **Lunacy**, que permitió 
definir la estructura general de las páginas y la identidad visual del proyecto.

La interfaz se caracteriza por:

- **Header fijo** con logo, nombre de la aplicación, menú de navegación principal 
  y botón de usuario con dropdown.
- **Footer** con enlaces a Detrás de cámara, Contacto y Términos de servicio.
- **Sliders interactivos** en la página principal y  sección de películas.
- **Modales dinámicos** para acciones como crear listas, grupos, editar perfil o confirmar 
  eliminaciones.
- **Dropdowns** para filtros, menú de usuario y opciones de marcar películas.
- **Efectos hover** en los pósters de películas, mostrando botones de acción al 
  pasar el cursor.
- **Animación de baraja de cartas** en la sección de sugerencias.
- **Chat en tiempo real** con interfaz tipo Telegram en la sección de comunidad.
- **Indicador de fortaleza de contraseña** en el formulario de registro.
- **Sistema de valoración** mediante corazones en la ficha de cada película.


**Página de inicio**
![Página de inicio](img/index.jpg)
![Página de inicio 2](img/index2.jpeg)


**Sección principal**
![Sección principal - Cartelera](img/seccion-principal.jpeg)
*Sliders de cartelera y próximos estrenos*

![Sección principal - Noticias](img/seccion-principal-2.jpeg)
*Noticias destacadas*


![Dropdown](img/dropdown.jpeg)


**Detalle de película**
![Detalle de película](img/detalle-pelicula.jpeg)
![Detalle de película 2](img/detalle-pelicula-2.jpeg)


**Sección Sugerir**
![Sugerir](img/sugerir.jpeg)


**Comunidad**
![Comunidad](img/comunidad.jpeg)


**Perfil de usuario**
![Perfil](img/perfil.jpeg)


**Panel de administración**
![Panel admin](img/panel-admin.jpeg)


---

### 8.4. Roles de la aplicación

TakeOne cuenta con dos roles de usuario:

**Usuario registrado**

Es el rol por defecto al crear una cuenta. Tiene acceso a todas las 
funcionalidades de la plataforma orientadas al consumo y la interacción:
explorar películas, crear y gestionar listas, unirse a grupos, enviar mensajes, 
añadir amigos, escribir comentarios, recibir sugerencias y consultar noticias.

**Administrador**

Rol con acceso al panel de administración. Tiene control total sobre el contenido 
y los usuarios de la plataforma: puede gestionar películas, noticias, cartelera y 
próximos estrenos, moderar comentarios y grupos, banear o eliminar usuarios, 
cambiar roles y consultar los formularios de contacto recibidos. El acceso al 
panel de administración está restringido y no es accesible desde la interfaz 
pública de la aplicación.

---

### 8.5. Usuarios de prueba

| Rol | Usuario | Contraseña |
|-----|---------|------------|
| Administrador | `adminmaria` | `Adminmaria1234` |
| Usuario registrado | `mariamv` | `maria1234` |
## 9. Lógica y codificación del proyecto

### 9.1. Principales procesos

#### Sistema de autenticación (login y registro)

El proceso de **registro** recoge mediante POST el nombre de usuario, email y 
contraseña. Antes de insertar, el backend comprueba en la base de datos que tanto 
el username como el email no estén ya en uso, devolviendo un mensaje de error en 
caso contrario. La contraseña nunca se guarda en texto plano: se cifra con 
`password_hash()` usando el algoritmo `PASSWORD_DEFAULT` de PHP antes de insertarla.

El proceso de **login** acepta tanto username como email en el mismo campo. El 
backend consulta la base de datos buscando coincidencia en ambos campos, verifica 
la contraseña con `password_verify()` y comprueba que la cuenta no esté suspendida 
(`activo = 0`). Si todo es correcto, guarda en `$_SESSION` el id, username, email, 
rol y avatar del usuario, y redirige según el rol: al panel de administración si 
es `admin`, o a la sección principal si es `usuario`.

---

#### Gestión de estados de películas

Cuando un usuario marca una película como pendiente, favorita o vista, el frontend 
envía una petición AJAX mediante POST al endpoint `actualizar-estado-pelicula.php`, 
indicando el `id_pelicula` y el `estado` deseado.

El endpoint valida que la sesión esté activa y que el estado recibido sea uno de 
los valores permitidos (`pendiente`, `vista`, `favorita` o vacío para eliminar el 
estado). A continuación comprueba si ya existe un registro para ese usuario y esa 
película en la tabla `usuarios_peliculas`:

- Si existe, actualiza el estado.
- Si no existe, inserta un nuevo registro.
- Si el estado recibido es vacío, elimina el estado (pone `NULL`).

La respuesta es siempre un JSON con el resultado de la operación, que el frontend 
usa para actualizar la interfaz sin recargar la página.

---

#### Chat en tiempo real (grupos y privado)

Tanto el chat de grupo como el chat privado funcionan mediante **polling**: el 
frontend realiza peticiones AJAX periódicas cada 4 segundos al endpoint 
correspondiente, solicitando únicamente los mensajes nuevos a partir del último 
`id_mensaje` recibido. Esto evita descargar mensajes ya mostrados y reduce la 
carga sobre el servidor.

**Chat de grupo:** Al enviar un mensaje, el frontend hace un POST a 
`mensajes-enviar.php`, que verifica que el usuario sea miembro del grupo antes 
de insertar el mensaje en la tabla `mensajes_grupo`. El endpoint devuelve el 
mensaje completo (con username y avatar) para que se muestre de forma inmediata 
sin esperar al siguiente ciclo de polling.

**Chat privado:** Funciona a través de `chat-privado-api.php`, que actúa como 
una pequeña API REST con dos acciones. La acción `mensajes` (GET) devuelve los 
mensajes nuevos entre los dos usuarios y marca como leídos los mensajes recibidos. 
La acción `enviar` (POST) valida que ambos usuarios sean amigos antes de insertar 
el mensaje, y devuelve el mensaje recién creado para renderizarlo al instante. En 
ambos chats, los mensajes se muestran en burbujas diferenciadas según si el 
mensaje es propio o ajeno.

---

#### Sistema de sugerencias

La sección Sugerir ofrece tres modos de recomendación, gestionados por 
`sugerir-api.php` mediante el parámetro `tipo`:

- **Aleatoria:** Selecciona una película al azar de la base de datos, excluyendo 
  las que el usuario ya ha marcado como vistas o favoritas.
- **Por géneros:** El usuario selecciona uno o varios géneros en un modal. La API 
  filtra las películas que pertenecen a esos géneros, excluyendo también las ya 
  vistas o favoritas.
- **Basada en gustos:** Sigue un sistema de tres niveles. Primero intenta 
  recomendar una película cuyos géneros coincidan con los géneros de las películas 
  que el usuario tiene en su lista. Si el usuario no tiene películas guardadas, 
  usa sus géneros favoritos del perfil. Si tampoco los tiene, cae en una selección 
  aleatoria como fallback. En todos los casos se excluyen las películas ya vistas 
  o favoritas.

Cada sugerencia se guarda automáticamente en la tabla `historial_sugerencias`, 
permitiendo al usuario consultar las películas que le han ido saliendo.

---

#### Sistema de amistades y mensajes privados

Las solicitudes de amistad se gestionan a través de la tabla `amistades`, que 
registra el usuario emisor, el receptor y el estado de la relación (`pendiente`, 
`aceptada` o `rechazada`). Cuando hay solicitudes pendientes o mensajes sin leer, 
aparece un aviso visual en el avatar del usuario en el header.

El acceso al chat privado entre dos usuarios solo es posible si existe una amistad 
en estado `aceptada` entre ambos. La función `sonAmigos()` de la API verifica esta 
condición en cada petición, devolviendo un error 403 si no se cumple.

---

### 9.2. Aspectos relevantes de la implementación

#### 9.2.1. Validación de datos

La validación se realiza en dos capas:

- **Frontend (JavaScript):** Validación visual en tiempo real en los formularios 
  de registro y login. El registro incluye un indicador de fortaleza de contraseña 
  y comprueba que se cumplan los requisitos mínimos antes de enviar el formulario. 
  Los mensajes de chat se limitan a 1000 caracteres mediante el atributo 
  `maxlength` y validación en JS.
- **Backend (PHP):** Todos los endpoints validan los datos recibidos antes de 
  ejecutar cualquier operación sobre la base de datos: se comprueba que los campos 
  obligatorios no estén vacíos, que los valores estén dentro de los permitidos 
  (por ejemplo, los estados de película) y que el usuario tenga los permisos 
  necesarios para realizar la acción. Las consultas usan **sentencias preparadas 
  con PDO** en todos los casos, lo que previene inyecciones SQL.

---

#### 9.2.2. Control de acceso

El control de acceso se basa en **sesiones PHP**. Al iniciar sesión, se almacena 
el rol del usuario en `$_SESSION['usuario']['rol']`. Todas las páginas protegidas 
comprueban al inicio si existe una sesión activa; si no, redirigen al login:

```php
if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit;
}
```

El panel de administración comprueba además que el rol sea `admin`. Los endpoints 
de la API también verifican la sesión en cada petición, devolviendo un código 
HTTP 401 si el usuario no está autenticado.

---

#### 9.2.3. Sistema de carpetas

El proyecto está organizado con la siguiente estructura de carpetas:

![Estructura de carpetas](img/Sistema-carpetas.png)

```
📁 takeone/
├── 📁 admin/          → Paneles de administración
├── 📁 api/            → Endpoints y lógica de la API interna
├── 📁 css/            → Hojas de estilo de usuario (styles.css) y de admin (admin.css)
├── 📁 img/            
├── 📁 includes/       → Archivos reutilizables (conexion.php, header.php, footer.php)
├── 📁 js/             → Scripts de JavaScript de la parte usuario
├── 📁 uploads/        → Imágenes subidas por los usuarios (avatares, portadas)
└── 📄 index.php      → Página principal de acceso
```


## 10. Despliegue web del proyecto

### 10.1. Requisitos hardware

Al tratarse de un hosting compartido gratuito, no se requiere infraestructura
propia. Los requisitos mínimos para ejecutar TakeOne son:

- Servidor web con soporte para **PHP**
- Gestor de base de datos **MySQL / MariaDB**
- Soporte para **subida de archivos** (imágenes de perfil, portadas de grupos...)
- Conexión a internet estable

### 10.2. Servidores utilizados

| Elemento | Detalle |
|----------|---------|
| **Hosting** | InfinityFree (hosting gratuito) |
| **Servidor web** | Apache |
| **Base de datos** | MySQL (proporcionada por InfinityFree) |
| **Dominio** | [takeone.gt.tc](https://takeone.gt.tc/) |
| **Entorno local** | XAMPP (Apache + MariaDB 10.4.28) |

### 10.3. Seguridad

- Las contraseñas de los usuarios se almacenan cifradas con `password_hash()`
  y se verifican con `password_verify()`, nunca en texto plano.
- Todas las consultas a la base de datos usan **sentencias preparadas con PDO**
  para prevenir inyecciones SQL.
- El acceso al panel de administración está restringido por rol mediante
  sesiones PHP. Cualquier intento de acceso sin los permisos adecuados
  redirige al inicio de sesión.
- Los endpoints de la API verifican la sesión activa en cada petición,
  devolviendo un error 401 si el usuario no está autenticado.
- Los datos recibidos del usuario se validan tanto en el frontend como en
  el backend antes de ejecutar cualquier operación.

### 10.4. Proceso de despliegue

El despliegue se realizó siguiendo estos pasos:

**1. Preparación de los archivos**

Antes de subir el proyecto, se adaptó el archivo de configuración de la
conexión a la base de datos (`includes/conexion.php`) con las credenciales
del entorno de producción de InfinityFree, sustituyendo los valores locales
de XAMPP.

**2. Subida de archivos**

Los archivos del proyecto se subieron mediante el **File Manager** del panel
de control de InfinityFree, dentro de la carpeta `htdocs`, que es el
directorio raíz del servidor web.

> ⚠️ Durante este proceso se detectó un problema: al subir los archivos,
> estos se colocaron dentro de una subcarpeta en lugar de en la raíz de
> `htdocs`. Se resolvió moviendo todos los archivos al nivel correcto
> directamente desde el File Manager.

**3. Creación e importación de la base de datos**

Se creó la base de datos desde el panel de InfinityFree y se importó el
script SQL con la estructura y los datos iniciales del proyecto.

> ⚠️ Se detectó que InfinityFree no permite el uso de **triggers** en la
> base de datos. Para resolverlo, la lógica que estaba implementada en los
> triggers se trasladó directamente al código PHP, manteniéndose así la
> misma funcionalidad sin depender de características no soportadas por
> el hosting.

**4. Verificación**

Una vez subidos los archivos e importada la base de datos, se verificó el
correcto funcionamiento de la aplicación accediendo al subdominio de
InfinityFree y probando las principales funcionalidades.



## 11.  Manual de usuario — TakeOne
 
- [Manual de usuario final](manual-usuario.md)
- [Manual de administrador](manual-admin.md)



## 12. Conclusiones y aspectos a mejorar

### Conclusiones

La experiencia de desarrollar TakeOne ha sido, en general, muy satisfactoria.
Desde el principio tenía claro lo que quería conseguir, y el hecho de haber
logrado que todo lo que había planificado funcionase hace que el balance final
sea muy positivo.

Al comienzo, el proyecto se me hacía un mundo. Tener que diseñar, estructurar
y desarrollar desde cero una plataforma completa con tantas funcionalidades
parecía una tarea enorme. Sin embargo, la clave estuvo en la constancia:
dedicándole tiempo todos los días, investigando cómo resolver cada problema
y avanzando paso a paso, lo que al principio parecía imposible fue haciéndose
cada vez más manejable.

Uno de los retos más importantes fue el **diseño visual** de la aplicación.
Crear una interfaz que fuese atractiva, coherente y con identidad propia no
es tarea fácil. Para resolverlo combiné el uso de herramientas como Stitch AI
con mi propia visión de lo que quería transmitir, hasta conseguir un estilo
que me representase.

Otro de los grandes desafíos fue la **sección de amigos y los chats privados**,
especialmente la parte de hacer que los avisos de mensajes sin leer apareciesen
exactamente donde debían: en el avatar del header, en el dropdown y sobre el
icono del chat correspondiente. Conseguir que todo esto funcionase de forma
coordinada y en tiempo real requirió mucha investigación y prueba y error.

La **sección de actividad** también supuso un reto inicial, sobre todo en la
gestión de los filtros para mostrar cada tipo de acción del usuario de forma
clara y ordenada.

A nivel de aprendizaje, este proyecto ha sido una de las experiencias más
completas del ciclo. He aprendido a **organizar y estructurar un proyecto
web de principio a fin**: desde el diseño de la base de datos hasta el
despliegue, pasando por la arquitectura de carpetas, la gestión de sesiones
y la creación de endpoints. También he aprendido a implementar un **sistema
de chat en tiempo real** mediante polling con AJAX y PHP, algo que antes
desconocía completamente y que ahora considero uno de los aspectos más
interesantes del proyecto.

En definitiva, TakeOne no ha sido solo un proyecto de fin de ciclo: ha sido
una forma de demostrarme que, con constancia y curiosidad, soy capaz de
construir algo en lo que creo desde cero.

---

### Aspectos a mejorar

Aunque el resultado final es satisfactorio, siempre hay margen de mejora.
Si dispusiera de más tiempo, me gustaría trabajar en los siguientes aspectos:

- **Mejorar la estética general:** pulir el diseño con efectos visuales más
  modernos, animaciones más cuidadas y una experiencia de usuario todavía
  más fluida e inmersiva.
- **Ampliar las funcionalidades del usuario:** añadir opciones como
  notificaciones en tiempo real, un sistema de seguimiento de usuarios
  (tipo "seguir" en redes sociales), o la posibilidad de compartir
  directamente una película o lista con un amigo.
- **Mejorar el sistema de recomendaciones:** implementar un algoritmo más
  sofisticado que tenga en cuenta no solo los géneros favoritos, sino también
  el historial de valoraciones del usuario para ofrecer sugerencias más
  precisas.
- **Optimizar el rendimiento:** revisar y optimizar las consultas SQL más
  complejas y mejorar los tiempos de carga en secciones con mucho contenido
  dinámico.
- **Adaptación móvil:** mejorar la experiencia en dispositivos móviles,
  especialmente en secciones como el chat o el detalle de película.



## 13. Bibliografía

| Recurso | Enlace |
|---------|--------|
| **Bootstrap 5** | [getbootstrap.com/docs](https://getbootstrap.com/docs/) |
| **Font Awesome** | [fontawesome.com/docs](https://fontawesome.com/docs) |
| **Stack Overflow** | [stackoverflow.com](https://stackoverflow.com/) |
| **W3Schools** | [w3schools.com](https://www.w3schools.com/) |
| **TMDB** | [themoviedb.org](https://www.themoviedb.org/) |
| **Stitch AI** | [stitch.withgoogle.com](https://stitch.withgoogle.com/) |
| **Claude (Anthropic)** | [claude.ai](https://claude.ai/) |



## Anexos

### Anexo I — Script SQL (DML)

Script con los datos iniciales de la base de datos de TakeOne. Incluye los
registros de todas las tablas necesarios para que la aplicación funcione
correctamente desde el primer arranque: géneros, plataformas, películas,
usuarios de prueba, grupos, noticias y demás datos de ejemplo.

📎 [Ver script SQL](datos_iniciales.sql)
