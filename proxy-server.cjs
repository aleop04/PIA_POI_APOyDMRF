/* eslint-disable @typescript-eslint/no-require-imports */

const express = require("express");
const { createProxyMiddleware } = require("http-proxy-middleware");

const app = express();

// proxy para socket.io: detecta peticiones que van a socket.io, redirigiendolas al servidor donde corre sockets (3000)
app.use(
    createProxyMiddleware({
        pathFilter: "/socket.io",
        target: "http://localhost:3000",
        changeOrigin: false,
        ws: true, // soporte de conexiones websocket
        headers: {
            "X-Forwarded-Proto": "https", // aunque la peticion llega a local host, el usuario entra desde la https de ngrok
            "X-Forwarded-Host": "goon-backfield-truce.ngrok-free.dev", // aunque la peticion llega a local host, el usuario entra desde la https de ngrok
        },
    }),
);

// proxy para laravel: envia el resto de las peticiones a laravel (8000). Maneja la app principal + servidor de sockets desde 1 solo puerto d entrada
app.use(
    createProxyMiddleware({
        target: "http://localhost:8000",
        changeOrigin: false,
        headers: {
            "X-Forwarded-Proto": "https", // aunque la peticion llega a local host, el usuario entra desde la https de ngrok
            "X-Forwarded-Host": "goon-backfield-truce.ngrok-free.dev", // aunque la peticion llega a local host, el usuario entra desde la https de ngrok
        },
    }),
);

// proxy ejecutandose en 8080
app.listen(8080, () => {
    console.log("Proxy corriendo en http://localhost:8080");
    console.log("Laravel: / -> http://localhost:8000");
    console.log("Socket.IO: /socket.io -> http://localhost:3000/socket.io");
});