const { Server } = require("socket.io");

const io = new Server(3000, {
    cors: {
        origin: "http://127.0.0.1:8000",
        methods: ["GET", "POST"],
    },
});

const onlineUsers = new Map();

function emitOnlineUsers(socket = null) {
    const onlineIds = Array.from(onlineUsers.keys());

    io.emit("users-online", onlineIds);

    if (socket) {
        socket.emit("users-online", onlineIds);
    }
}

io.on("connection", (socket) => {
    console.log("Auth recibido:", socket.handshake.auth);

    const userId = socket.handshake.auth.userId;

    console.log("Usuario conectado:", socket.id, "User ID:", userId);

    if (userId) {
        const id = Number(userId);

        if (!onlineUsers.has(id)) {
            onlineUsers.set(id, new Set());
        }

        onlineUsers.get(id).add(socket.id);

        emitOnlineUsers(socket);
    }

    socket.on("join-chat", (conversationId) => {
        socket.join(`chat-${conversationId}`);
        console.log(`Unido a chat-${conversationId}`);
    });

    socket.on("send-message", (message) => {
        socket.to(`chat-${message.conversation_id}`).emit("receive-message", message);
    });

    socket.on("new-conversation", (conversation) => {
        socket.broadcast.emit("conversation-created", conversation);
    });

    socket.on("update-conversation", (conversation) => {
        socket.broadcast.emit("conversation-updated", conversation);
    });

    socket.on("get-users-online", () => {
        emitOnlineUsers(socket);
    });

    socket.on("disconnect", async () => {
        console.log("Usuario desconectado:", socket.id);

        if (!userId) return;

        const id = Number(userId);
        const sockets = onlineUsers.get(id);

        if (sockets) {
            sockets.delete(socket.id);

            if (sockets.size === 0) {
                onlineUsers.delete(id);

                try {
                    await fetch("http://127.0.0.1:8000/socket/user-offline", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                        },
                        body: JSON.stringify({
                            user_id: id,
                        }),
                    });
                } catch (error) {
                    console.error("Error avisando user-offline a Laravel:", error);
                }
            }
        }

        emitOnlineUsers();
    });
});

console.log("Socket corriendo en http://localhost:3000");