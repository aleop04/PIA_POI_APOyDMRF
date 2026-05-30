/* eslint-disable @typescript-eslint/no-require-imports */

// importamos socket.io
const { Server } = require("socket.io");

// creacion de servidor en puerto 3000. el proxy en 8080 redirige peticiones de socket.io a este servidor
const io = new Server(3000, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    },
});

// usuarios conectados actualmente
const onlineUsers = new Map();

const groupCallSockets = new Map();
const activeGroupCalls = new Map();
const activeGroupCallTypes = new Map();

function emitOnlineUsers(socket = null) {
    const onlineIds = Array.from(onlineUsers.keys());

    io.emit("users-online", onlineIds);

    if (socket) {
        socket.emit("users-online", onlineIds);
    }
}

function emitGroupCallParticipants(callId) {
    const participants = activeGroupCalls.get(callId);
    const room = `group-call-${callId}`;

    io.to(room).emit("group-call-participants-updated", {
        conversationId: callId,
        participants: participants ? Array.from(participants.values()) : [],
    });
}

io.on("connection", (socket) => {
    console.log("Auth recibido:", socket.handshake.auth);

    // obtencion de id de usuario conectado
    const userId = socket.handshake.auth.userId;

    console.log("Usuario conectado:", socket.id, "User ID:", userId);

    if (userId) {
        const id = Number(userId);

        socket.join(`user-${id}`); // para llamada de voz 1 a 1

        if (!onlineUsers.has(id)) {
            onlineUsers.set(id, new Set());
        }

        onlineUsers.get(id).add(socket.id);

        emitOnlineUsers(socket);
    }

    socket.on("join-chat", (conversationId) => {
        const room = `chat-${conversationId}`;

        if (socket.rooms.has(room)) {
            return;
        }

        socket.join(room);
        console.log(`Unido a ${room}`);
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

    // para llamade de voz 1 a 1

    socket.on("call-user", ({ toUserId, fromUser, conversationId, callType, offer }) => {
        io.to(`user-${toUserId}`).emit("incoming-call", {
            fromUser,
            conversationId,
            callType,
            offer,
        });
    });

    socket.on("call-offer", ({ toUserId, offer }) => {
        io.to(`user-${toUserId}`).emit("call-offer", {
            fromUserId: Number(userId),
            offer,
        });
    });

    socket.on("call-answer", ({ toUserId, answer }) => {
        io.to(`user-${toUserId}`).emit("call-answer", {
            fromUserId: Number(userId),
            answer,
        });
    });

    socket.on("ice-candidate", ({ toUserId, candidate }) => {
        io.to(`user-${toUserId}`).emit("ice-candidate", {
            fromUserId: Number(userId),
            candidate,
        });
    });

    socket.on("reject-call", ({ toUserId }) => {
        io.to(`user-${toUserId}`).emit("call-rejected", {
            fromUserId: Number(userId),
        });
    });

    socket.on("end-call", ({ toUserId }) => {
        io.to(`user-${toUserId}`).emit("call-ended", {
            fromUserId: Number(userId),
        });
    });

    //

    // llamadas de voz + video grupales
    socket.on("group-call-user", ({ toUserIds, fromUser, conversationId, callType, group }) => {
        const callId = Number(conversationId);
        const normalizedCallType = callType === "video" ? "video" : "voice";

        activeGroupCallTypes.set(callId, normalizedCallType);

        const targetUserIds = toUserIds
            .map((id) => Number(id))
            .filter((id) => id !== Number(fromUser.id));

        io.to(`chat-${callId}`).emit("active-group-call-status", {
            conversationId: callId,
            active: true,
            callType: normalizedCallType,
        });

        io.to(`chat-${callId}`).emit("incoming-group-call", {
            fromUser,
            conversationId: callId,
            callType: normalizedCallType,
            group,
            toUserIds: targetUserIds,
        });
    });

    socket.on("get-active-group-calls", () => {
        const calls = [];

        for (const [conversationId, participants] of activeGroupCalls.entries()) {
            if (participants.size > 0) {
                calls.push({
                    conversationId: Number(conversationId),
                    callType: activeGroupCallTypes.get(Number(conversationId)) ?? "voice",
                    participants: Array.from(participants.values()),
                });
            }
        }

        socket.emit("active-group-calls", calls);
    });

    socket.on("get-active-group-call", ({ conversationId }) => {
        const callId = Number(conversationId);
        const participants = activeGroupCalls.get(callId);

        socket.emit("active-group-call-status", {
            conversationId: callId,
            active: !!participants && participants.size > 0,
            callType: activeGroupCallTypes.get(callId) ?? "voice",
            participants: participants ? Array.from(participants.values()) : [],
        });
    });

    socket.on("join-group-call", ({ conversationId, fromUser }) => {
        const callId = Number(conversationId);
        const room = `group-call-${callId}`;
        const id = Number(fromUser.id);

        if (!activeGroupCalls.has(callId)) {
            activeGroupCalls.set(callId, new Map());
        }

        if (!groupCallSockets.has(callId)) {
            groupCallSockets.set(callId, new Map());
        }

        const participants = activeGroupCalls.get(callId);
        const socketsByUser = groupCallSockets.get(callId);

        if (!socketsByUser.has(id)) {
            socketsByUser.set(id, new Set());
        }

        socketsByUser.get(id).add(socket.id);

        const existingParticipants = Array.from(participants.values()).filter(
            (participant) => Number(participant.id) !== id
        );

        participants.set(id, {
            ...fromUser,
            id,
        });

        socket.join(room);

        socket.emit("group-call-existing-participants", {
            conversationId: callId,
            participants: existingParticipants,
        });

        socket.to(room).emit("group-call-user-joined", {
            user: {
                ...fromUser,
                id,
            },
            conversationId: callId,
        });

        emitGroupCallParticipants(callId);

        console.log(`Usuario ${id} unido a llamada grupal ${callId}`);
    });

    socket.on("group-call-offer", ({ toUserId, conversationId, offer }) => {
        io.to(`user-${toUserId}`).emit("group-call-offer", {
            fromUserId: Number(userId),
            conversationId,
            offer,
        });
    });

    socket.on("group-call-answer", ({ toUserId, conversationId, answer }) => {
        io.to(`user-${toUserId}`).emit("group-call-answer", {
            fromUserId: Number(userId),
            conversationId,
            answer,
        });
    });

    socket.on("group-ice-candidate", ({ toUserId, conversationId, candidate }) => {
        io.to(`user-${toUserId}`).emit("group-ice-candidate", {
            fromUserId: Number(userId),
            conversationId,
            candidate,
        });
    });

    socket.on("leave-group-call", ({ conversationId }) => {
        const callId = Number(conversationId);
        const room = `group-call-${callId}`;
        const id = Number(userId);

        const participants = activeGroupCalls.get(callId);
        const socketsByUser = groupCallSockets.get(callId);

        if (!participants || !socketsByUser) {
            socket.leave(room);

            return;
        }

        const userSockets = socketsByUser.get(id);

        if (userSockets) {
            userSockets.delete(socket.id);

            if (userSockets.size === 0) {
                socketsByUser.delete(id);
                participants.delete(id);
            }
        }

        const remainingParticipants = Array.from(participants.values());

        socket.to(room).emit("group-call-user-left", {
            userId: id,
            conversationId: callId,
            remainingParticipants,
        });

        emitGroupCallParticipants(callId);

        console.log(
            `Usuario ${id} salió de llamada grupal ${callId}. Quedan: ${remainingParticipants.length}`
        );

        if (remainingParticipants.length === 0) {
            io.to(`chat-${callId}`).emit("group-call-ended", {
                conversationId: callId,
            });

            io.to(`chat-${callId}`).emit("active-group-call-status", {
                conversationId: callId,
                active: false,
            });

            activeGroupCalls.delete(callId);
            groupCallSockets.delete(callId);
            activeGroupCallTypes.delete(callId);
        }

        socket.leave(room);
    });
    //

    socket.on("disconnect", async () => {
        console.log("Usuario desconectado:", socket.id);

        if (!userId) {
            return;
        }

        const id = Number(userId);

        for (const [conversationId, participants] of activeGroupCalls.entries()) {
            const callId = Number(conversationId);
            const socketsByUser = groupCallSockets.get(callId);

            if (!socketsByUser || !socketsByUser.has(id)) {
                continue;
            }

            const userSockets = socketsByUser.get(id);

            if (userSockets) {
                userSockets.delete(socket.id);

                if (userSockets.size === 0) {
                    socketsByUser.delete(id);
                    participants.delete(id);
                }
            }

            const room = `group-call-${callId}`;
            const remainingParticipants = Array.from(participants.values());

            socket.to(room).emit("group-call-user-left", {
                userId: id,
                conversationId: callId,
                remainingParticipants,
            });

            emitGroupCallParticipants(callId);

            if (remainingParticipants.length === 0) {
                io.to(`chat-${callId}`).emit("group-call-ended", {
                    conversationId: callId,
                });

                io.to(`chat-${callId}`).emit("active-group-call-status", {
                    conversationId: callId,
                    active: false,
                });

                activeGroupCalls.delete(callId);
                groupCallSockets.delete(callId);
                activeGroupCallTypes.delete(callId);
            }

            socket.leave(room);
        }

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

console.log("Socket corriendo en http://0.0.0.0:3000");