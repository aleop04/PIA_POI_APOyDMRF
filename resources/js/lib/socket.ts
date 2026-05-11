import { io, Socket } from 'socket.io-client';

let socket: Socket | null = null;

export function connectSocket(userId: number) {
    if (socket?.connected) {
        return socket;
    }

    socket = io('http://localhost:3000', {
        auth: {
            userId,
        },
        reconnection: true,
        reconnectionAttempts: Infinity,
        reconnectionDelay: 500,
        reconnectionDelayMax: 3000,
        timeout: 10000,
    });

    return socket;
}

export function getSocket() {
    return socket;
}