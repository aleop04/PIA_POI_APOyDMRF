import type { Socket } from 'socket.io-client';
import { io } from 'socket.io-client';

let socket: Socket | null = null;

export function connectSocket(userId: number) {
    if (socket?.connected) {
        return socket;
    }

    const socketUrl = window.location.origin;

    socket = io(socketUrl, {
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