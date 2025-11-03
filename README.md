# 🚀 Mindfulness 

---

## ✨ Descripción

`Mindfulness404` es un proyecto integrando **Vue 3**, **Vue Router**, y **Bootstrap 5**, con un enfoque en:

* 🎨 **Diseño calmado** y animado: degradados suaves, transiciones fluidas y animaciones sutiles.
* 🔧 **Arquitectura modular**: Layouts públicos, autenticados y específicos por rol.
* 📱 **Responsividad** total con Bootstrap
* 💡 **Buenas prácticas** de accesibilidad y SEO básico

---

## 📂 Estructura del Repositorio

```bash
├── src/
│   ├── assets/                      # Imágenes y SVGs
│   │   └── images/errorBG.png       # Fondo mindfulness para la 404
│   ├── components/                  # Componentes globales (Sidebar, Navbar)
│   ├── layouts/                     # Layouts de Vue Router
│   │   ├── PublicLayout.vue
│   │   ├── LoginLayout.vue
│   │   └── ProfesorLayout.vue
│   ├── views/
│   │   ├── Index.vue
│   │   ├── Login.vue
│   │   ├── profesor/
│   │   │   ├── Home.vue
│   │   │   └── Asignaciones.vue
│   │   └── Error404.vue             # Página de error personalizada
│   ├── router/
│   │   └── index.js                 # Configuración de rutas anidadas
│   └── App.vue
├── public/
│   └── index.html
├── package.json
└── README.md                        # (tú estás aquí)
```

---

## 🚀 Tecnologías

* 📦 **Vue 3**
* 🔄 **Vue Router**
* 🎨 **Bootstrap 5**
* 🎨 **CSS3**
* ⚙️ **Axios** para llamadas HTTP

---

## ⚙️ Instalación

1. Clona el repositorio:

   ```bash
   ```

git clone [https://github.com/tu-usuario/mindfulness404.git](https://github.com/tu-usuario/mindfulness404.git)
cd mindfulness404

````

2. Instala dependencias:
   ```bash
npm install
````

3. Levanta el servidor de desarrollo:

   ```bash
   ```

npm run serve

```

4. Abre tu navegador en `http://localhost:8080` y disfruta.

---

## 🎯 Uso

- Navega entre rutas públicas: `/`, `/login`, `/sobre-nosotros`, `/contacto`.
- Accede a rutas protegidas: `/app/profesor`, `/app/profesor/asignaciones`, `/app/estudiante`, `/app/administrador`. 
- Fuerza una ruta inexistente (p.ej. `/ruta-otro`) y prueba la **página 404** con diseño **mindfulness**.

---

## 🎨 Diseño 404

- **Animaciones**: icono flotante, pulsos y rotaciones.
- **Colores**: degradados RGB enfocados en calma y contraste de error.
- **Transiciones**: `fadeInUp`, `pulse`, `float`.
- **Botón** estilo `btn-exit` con degradado y hover animado.

---

## 🤝 Contribuciones

¡Las contribuciones son bienvenidas! Si encuentras mejoras de diseño, bugs o ideas nuevas:

1. Haz un fork. 🍴
2. Crea tu feature branch: `git checkout -b feature/nueva-idea`
3. Commit: `git commit -m "feat: descripción breve"`
4. Push: `git push origin feature/nueva-idea`
5. Abre un Pull Request.

---

## 📜 Licencia

Este proyecto está bajo la licencia **MIT**. ¡Siéntete libre de usarlo y adaptarlo!

---

<p align="center">
  <img src="https://img.shields.io/badge/Vue-3.2.0-4FC08D?logo=vue" alt="Vue 3">
  <img src="https://img.shields.io/badge/Bootstrap-5.2.0-563D7C?logo=bootstrap" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="MIT License">
</p>

```
