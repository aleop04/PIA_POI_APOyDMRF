<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import ActiveCallScreen from '@/components/Calls/ActiveCallScreen.vue';
import IncomingCallModal from '@/components/Calls/IncomingCallModal.vue';
import OutgoingCallModal from '@/components/Calls/OutgoingCallModal.vue';
import { getSocket } from '@/lib/socket';

type CallUser = {
    id: number;
    username?: string;
    first_name?: string;
    last_name?: string;
    profile_photo?: string | null;
};

type CallType = 'voice' | 'video';

type IncomingCall = {
    fromUser: CallUser;
    conversationId: number;
    callType: CallType;
    offer: RTCSessionDescriptionInit;
};

const pendingOutgoingCall = ref<{
    toUser: CallUser;
    fromUser: CallUser;
    conversationId: number;
    callType: CallType;
} | null>(null);

type GroupCallInfo = {
    id: number;
    name: string | null;
    photo: string | null;
};

type GroupIncomingCall = {
    fromUser: CallUser;
    conversationId: number;
    callType: CallType;
    group: GroupCallInfo;
    toUserIds?: number[];
};

type GroupOutgoingCall = {
    fromUser: CallUser;
    conversationId: number;
    callType: CallType;
    members: CallUser[];
    group: GroupCallInfo;
};

const incomingCall = ref<IncomingCall | null>(null);
const activeCallUser = ref<CallUser | null>(null);
const callStatus = ref<'idle' | 'calling' | 'ringing' | 'connected'>('idle');

const pendingIceCandidates = ref<RTCIceCandidateInit[]>([]);
const localStream = ref<MediaStream | null>(null);
const remoteStream = ref<MediaStream | null>(null);
const peerConnection = ref<RTCPeerConnection | null>(null);

const remoteAudio = ref<HTMLAudioElement | null>(null);
const localVideo = ref<HTMLVideoElement | null>(null);
const remoteVideo = ref<HTMLVideoElement | null>(null);

const currentCallType = ref<CallType>('voice');
const localMicEnabled = ref(true);

const missedCallMessage = ref('');
const outgoingCallTimeout = ref<number | null>(null);

const page = usePage();

const authUser = computed(() => page.props.auth.user as unknown as CallUser);

const pendingGroupCall = ref<GroupOutgoingCall | null>(null);
const incomingGroupCall = ref<GroupIncomingCall | null>(null);
const groupCallActive = ref(false);
const groupConversationId = ref<number | null>(null);
const groupMembers = ref<CallUser[]>([]);
const activeGroupCallInfo = ref<GroupCallInfo | null>(null);

const groupPeerConnections = ref<Map<number, RTCPeerConnection>>(new Map());
const groupPendingIceCandidates = ref<Map<number, RTCIceCandidateInit[]>>(new Map());
const groupAudioElements = ref<Map<number, HTMLAudioElement>>(new Map());
const groupRemoteStreams = ref<Map<number, MediaStream>>(new Map());

function createPeerConnection(peerUserId: number) {
    const socket = getSocket();

    const pc = new RTCPeerConnection({
        iceServers: [
            {
                urls: [
                    'stun:stun.l.google.com:19302',
                ],
            },
            {
                urls: [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:443',
                    'turns:openrelay.metered.ca:443',
                ],
                username: 'openrelayproject',
                credential: 'openrelayproject',
            },
        ],
    });

    pc.onconnectionstatechange = () => {
        console.log('Estado WebRTC:', pc.connectionState);
    };

    pc.oniceconnectionstatechange = () => {
        console.log('Estado ICE:', pc.iceConnectionState);
    };

    pc.onicecandidate = (event) => {
        if (!event.candidate || !socket) {
            return;
        }

        socket.emit('ice-candidate', {
            toUserId: peerUserId,
            candidate: event.candidate,
        });
    };

    pc.ontrack = (event) => {
        const [stream] = event.streams;

        if (!stream) {
            return;
        }

        remoteStream.value = stream;

        if (remoteAudio.value) {
            remoteAudio.value.srcObject = stream;
            remoteAudio.value.muted = false;
            remoteAudio.value.volume = 1;

            remoteAudio.value.play().catch((error) => {
                console.error('Audio remoto bloqueado:', error);
            });
        }

        if (remoteVideo.value) {
            remoteVideo.value.srcObject = stream;

            remoteVideo.value.play().catch((error) => {
                console.error('Video remoto bloqueado:', error);
            });
        }
    };

    peerConnection.value = pc;

    return pc;
}

function createGroupPeerConnection(peerUserId: number) {
    const socket = getSocket();

    const pc = new RTCPeerConnection({
        iceServers: [
            {
                urls: [
                    'stun:stun.l.google.com:19302',
                ],
            },
            {
                urls: [
                    'turn:openrelay.metered.ca:80',
                    'turn:openrelay.metered.ca:443',
                    'turns:openrelay.metered.ca:443',
                ],
                username: 'openrelayproject',
                credential: 'openrelayproject',
            },
        ],
    });

    pc.onicecandidate = (event) => {
        if (!event.candidate || !socket || !groupConversationId.value) {
            return;
        }

        socket.emit('group-ice-candidate', {
            toUserId: peerUserId,
            conversationId: groupConversationId.value,
            candidate: event.candidate,
        });
    };

    pc.ontrack = (event) => {
        const [stream] = event.streams;

        if (!stream) {
            return;
        }

        groupRemoteStreams.value.set(peerUserId, stream);

        let audio = groupAudioElements.value.get(peerUserId);

        if (!audio) {
            audio = new Audio();
            audio.autoplay = true;
            audio.volume = 1;

            groupAudioElements.value.set(peerUserId, audio);
        }

        audio.srcObject = stream;

        audio.play().catch((error) => {
            console.error('Audio grupal bloqueado:', error);
        });
    };

    groupPeerConnections.value.set(peerUserId, pc);

    return pc;
}

function setVisibleGroupMembers(members: CallUser[]) {
    groupMembers.value = members.filter(
        (member) => Number(member.id) !== Number(authUser.value.id),
    );
}

async function getGroupLocalMediaStream(callType: CallType) {
    if (localStream.value) {
        return localStream.value;
    }

    const stream = await navigator.mediaDevices.getUserMedia({
        audio: {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true,
        },
        video:
            callType === 'video'
                ? {
                      width: { ideal: 1280 },
                      height: { ideal: 720 },
                      facingMode: 'user',
                  }
                : false,
    });

    stream.getAudioTracks().forEach((track) => {
        track.enabled = localMicEnabled.value;
    });

    localStream.value = stream;

    return stream;
}

async function flushGroupPendingIceCandidates(peerUserId: number) {
    const pc = groupPeerConnections.value.get(peerUserId);

    if (!pc) {
        return;
    }

    const candidates = groupPendingIceCandidates.value.get(peerUserId) ?? [];

    for (const candidate of candidates) {
        try {
            await pc.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (error) {
            console.error('Error agregando ICE grupal pendiente:', error);
        }
    }

    groupPendingIceCandidates.value.delete(peerUserId);
}

async function syncGroupParticipants(participants: CallUser[]) {
    setVisibleGroupMembers(participants);

    const visibleParticipants = participants.filter(
        (participant) => Number(participant.id) !== Number(authUser.value.id),
    );

    for (const participant of visibleParticipants) {
        await createOfferForGroupUser(participant);
    }
}

function startOutgoingGroupCall(event: Event) {
    const customEvent = event as CustomEvent<GroupOutgoingCall>;

    if (callStatus.value !== 'idle') {
        console.error('Ya hay una llamada en curso.');

        return;
    }

    pendingGroupCall.value = customEvent.detail;
}

function closeGroupPeerConnection(peerUserId: number) {
    const pc = groupPeerConnections.value.get(peerUserId);

    if (pc) {
        pc.close();
        groupPeerConnections.value.delete(peerUserId);
    }

    const audio = groupAudioElements.value.get(peerUserId);

    if (audio) {
        audio.pause();
        audio.srcObject = null;
        groupAudioElements.value.delete(peerUserId);
    }

    groupPendingIceCandidates.value.delete(peerUserId);
    groupRemoteStreams.value.delete(peerUserId);

    groupMembers.value = groupMembers.value.filter(
        (member) => member.id !== peerUserId,
    );
}

async function confirmOutgoingGroupCall() {
    if (!pendingGroupCall.value) {
        return;
    }

    const socket = getSocket();

    if (!socket) {
        return;
    }

    const call = pendingGroupCall.value;
    const toUserIds = call.members
        .filter((member) => member.id !== call.fromUser.id)
        .map((member) => member.id);

    try {
        await getGroupLocalMediaStream(call.callType);

        callStatus.value = 'connected';
        currentCallType.value = call.callType;
        groupCallActive.value = true;
        groupConversationId.value = call.conversationId;
        groupMembers.value = [];
        activeCallUser.value = null;
        activeGroupCallInfo.value = call.group;

        socket.emit('join-group-call', {
            conversationId: call.conversationId,
            fromUser: call.fromUser,
        });

        console.log('Miembros grupo:', call.members);
        console.log('IDs destino:', toUserIds);

        socket.emit('group-call-user', {
            toUserIds,
            fromUser: call.fromUser,
            conversationId: call.conversationId,
            callType: call.callType,
            group: call.group,
        });

        pendingGroupCall.value = null;
    } catch (error) {
        console.error('No se pudo iniciar llamada grupal:', error);
        resetCallState();
    }
}

async function acceptGroupCall() {
    if (!incomingGroupCall.value) {
        return;
    }

    const socket = getSocket();

    if (!socket) {
        return;
    }

    const call = incomingGroupCall.value;

    try {
        await getGroupLocalMediaStream(call.callType);

        callStatus.value = 'connected';
        currentCallType.value = call.callType;
        groupCallActive.value = true;
        groupConversationId.value = call.conversationId;
        activeCallUser.value = call.fromUser;
        groupMembers.value = [call.fromUser];
        activeGroupCallInfo.value = call.group;

        socket.emit('join-group-call', {
            conversationId: call.conversationId,
            fromUser: authUser.value,
        });

        incomingGroupCall.value = null;
    } catch (error) {
        console.error('No se pudo aceptar llamada grupal:', error);
        resetCallState();
    }
}

function rejectGroupCall() {
    incomingGroupCall.value = null;
    resetCallState();
}

async function createOfferForGroupUser(user: CallUser) {
    const socket = getSocket();

    if (!socket || !groupConversationId.value || !localStream.value) {
        return;
    }

    if (Number(user.id) === Number(authUser.value.id)) {
        return;
    }

    // Evita que ambos usuarios creen offer al mismo tiempo.
    // Solo el ID menor inicia la conexión WebRTC.
    if (Number(authUser.value.id) > Number(user.id)) {
        return;
    }

    const existingPc = groupPeerConnections.value.get(user.id);

    if (existingPc) {
        const brokenConnection =
            existingPc.connectionState === 'failed' ||
            existingPc.connectionState === 'closed' ||
            existingPc.connectionState === 'disconnected' ||
            existingPc.iceConnectionState === 'failed' ||
            existingPc.iceConnectionState === 'closed' ||
            existingPc.iceConnectionState === 'disconnected';

        if (!brokenConnection) {
            return;
        }

        closeGroupPeerConnection(user.id);
    }

    const pc = createGroupPeerConnection(user.id);

    localStream.value.getTracks().forEach((track) => {
        pc.addTrack(track, localStream.value!);
    });

    const offer = await pc.createOffer();

    await pc.setLocalDescription(offer);

    socket.emit('group-call-offer', {
        toUserId: user.id,
        conversationId: groupConversationId.value,
        offer,
    });
}

async function handleGroupOffer({
    fromUserId,
    conversationId,
    offer,
}: {
    fromUserId: number;
    conversationId: number;
    offer: RTCSessionDescriptionInit;
}) {
    const socket = getSocket();

    if (!socket) {
        return;
    }

    groupConversationId.value = conversationId;

    const stream = await getGroupLocalMediaStream(currentCallType.value);

    let pc = groupPeerConnections.value.get(fromUserId);

    if (!pc) {
        pc = createGroupPeerConnection(fromUserId);

        stream.getTracks().forEach((track) => {
            pc!.addTrack(track, stream);
        });
    }

    if (pc.signalingState !== 'stable') {
        console.warn('Offer grupal ignorada:', {
            fromUserId,
            signalingState: pc.signalingState,
        });

        return;
    }

    await pc.setRemoteDescription(new RTCSessionDescription(offer));
    await flushGroupPendingIceCandidates(fromUserId);

    const answer = await pc.createAnswer();

    await pc.setLocalDescription(answer);

    socket.emit('group-call-answer', {
        toUserId: fromUserId,
        conversationId,
        answer,
    });

    callStatus.value = 'connected';
    groupCallActive.value = true;
}

async function handleGroupAnswer({
    fromUserId,
    answer,
}: {
    fromUserId: number;
    answer: RTCSessionDescriptionInit;
}) {
    const pc = groupPeerConnections.value.get(fromUserId);

    if (!pc) {
        return;
    }

    if (pc.signalingState !== 'have-local-offer') {
        console.warn('Answer grupal ignorada:', {
            fromUserId,
            signalingState: pc.signalingState,
        });

        return;
    }

    try {
        await pc.setRemoteDescription(new RTCSessionDescription(answer));
        await flushGroupPendingIceCandidates(fromUserId);
    } catch (error) {
        console.error('Error aplicando answer grupal:', error);
    }
}

async function handleGroupIceCandidate({
    fromUserId,
    candidate,
}: {
    fromUserId: number;
    candidate: RTCIceCandidateInit;
}) {
    const pc = groupPeerConnections.value.get(fromUserId);

    if (!pc || !pc.remoteDescription) {
        const current = groupPendingIceCandidates.value.get(fromUserId) ?? [];

        current.push(candidate);
        groupPendingIceCandidates.value.set(fromUserId, current);

        return;
    }

    try {
        await pc.addIceCandidate(new RTCIceCandidate(candidate));
    } catch (error) {
        console.error('Error agregando ICE grupal:', error);
    }
}
async function getLocalMediaStream(callType: CallType) {
    if (localStream.value) {
        return localStream.value;
    }

    const stream = await navigator.mediaDevices.getUserMedia({
        audio: {
            echoCancellation: true,
            noiseSuppression: true,
            autoGainControl: true,
        },
        video:
            callType === 'video'
                ? {
                      width: { ideal: 1280 },
                      height: { ideal: 720 },
                      facingMode: 'user',
                  }
                : false,
    });

    stream.getAudioTracks().forEach((track) => {
        track.enabled = localMicEnabled.value;
    });

    localStream.value = stream;

    if (callType === 'video' && localVideo.value) {
        localVideo.value.srcObject = stream;
        localVideo.value.muted = true;
        await localVideo.value.play().catch(() => {});
    }

    return stream;
}

async function attachLocalVideo() {
    if (currentCallType.value !== 'video') {
        return;
    }

    await nextTick();

    if (!localVideo.value || !localStream.value) {
        return;
    }

    localVideo.value.srcObject = localStream.value;
    localVideo.value.muted = true;

    await localVideo.value.play().catch(() => {});
}

async function attachRemoteVideo() {
    if (currentCallType.value !== 'video') {
        return;
    }

    await nextTick();

    if (!remoteVideo.value || !remoteStream.value) {
        return;
    }

    remoteVideo.value.srcObject = remoteStream.value;

    await remoteVideo.value.play().catch((error) => {
        console.error('No se pudo reproducir video remoto:', error);
    });
}

async function attachVideos() {
    await attachLocalVideo();
    await attachRemoteVideo();
}

function toggleLocalMic() {
    localMicEnabled.value = !localMicEnabled.value;

    if (!localStream.value) {
        return;
    }

    localStream.value.getAudioTracks().forEach((track) => {
        track.enabled = localMicEnabled.value;
    });
}

function clearOutgoingCallTimeout() {
    if (outgoingCallTimeout.value) {
        clearTimeout(outgoingCallTimeout.value);
        outgoingCallTimeout.value = null;
    }
}

function stopLocalStream() {
    if (!localStream.value) {
        return;
    }

    localStream.value.getTracks().forEach((track) => track.stop());
    localStream.value = null;
}

function closePeerConnection() {
    if (peerConnection.value) {
        peerConnection.value.close();
        peerConnection.value = null;
    }

    if (remoteAudio.value) {
        remoteAudio.value.srcObject = null;
    }

    if (remoteVideo.value) {
        remoteVideo.value.srcObject = null;
    }

    if (localVideo.value) {
        localVideo.value.srcObject = null;
    }
}

function resetCallState() {
    incomingCall.value = null;
    pendingOutgoingCall.value = null;
    activeCallUser.value = null;
    callStatus.value = 'idle';
    currentCallType.value = 'voice';
    localMicEnabled.value = true;
    pendingIceCandidates.value = [];
    remoteStream.value = null;
    incomingGroupCall.value = null;
    pendingGroupCall.value = null;
    groupCallActive.value = false;
    groupConversationId.value = null;
    groupMembers.value = [];
    activeGroupCallInfo.value = null;

    groupPeerConnections.value.forEach((pc) => pc.close());
    groupPeerConnections.value.clear();
    groupRemoteStreams.value.clear();

    groupAudioElements.value.forEach((audio) => {
        audio.pause();
        audio.srcObject = null;
    });
    groupAudioElements.value.clear();

    groupPendingIceCandidates.value.clear();

    clearOutgoingCallTimeout();
    closePeerConnection();
    stopLocalStream();
}

function startOutgoingCall(event: Event) {
    const customEvent = event as CustomEvent<{
        toUser: CallUser;
        fromUser: CallUser;
        conversationId: number;
        callType: CallType;
    }>;

    if (callStatus.value !== 'idle') {
        console.error('Ya hay una llamada en curso.');

        return;
    }

    pendingOutgoingCall.value = customEvent.detail;
    currentCallType.value = customEvent.detail.callType;
}

async function confirmOutgoingCall() {
    if (!pendingOutgoingCall.value) {
        return;
    }

    const socket = getSocket();

    if (!socket) {
        console.error('Socket no conectado.');

        return;
    }

    const { toUser, fromUser, conversationId, callType } = pendingOutgoingCall.value;

    try {
        callStatus.value = 'calling';
        activeCallUser.value = toUser;
        currentCallType.value = callType;

        const stream = await getLocalMediaStream(callType);
        const pc = createPeerConnection(toUser.id);

        stream.getTracks().forEach((track) => {
            pc.addTrack(track, stream);
        });

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        socket.emit('call-user', {
            toUserId: toUser.id,
            fromUser,
            conversationId,
            callType,
            offer,
        });

        pendingOutgoingCall.value = null;
        clearOutgoingCallTimeout();

        outgoingCallTimeout.value = window.setTimeout(() => {
            if (callStatus.value === 'calling') {

                socket.emit('end-call', {
                    toUserId: toUser.id,
                });

                missedCallMessage.value = 'Llamada perdida';
                setTimeout(() => {
                    missedCallMessage.value = '';
                }, 3000);
                resetCallState();
            }
        }, 15000);
        await attachLocalVideo();
    } catch (error) {
        console.error('No se pudo iniciar la llamada:', error);
        resetCallState();
    }
}

function cancelOutgoingCall() {
    pendingOutgoingCall.value = null;
}

async function flushPendingIceCandidates() {
    if (!peerConnection.value) {
        return;
    }

    for (const candidate of pendingIceCandidates.value) {
        try {
            await peerConnection.value.addIceCandidate(new RTCIceCandidate(candidate));
        } catch (error) {
            console.error('Error agregando ICE pendiente:', error);
        }
    }

    pendingIceCandidates.value = [];
}

async function acceptCall() {
    if (!incomingCall.value) {
        return;
    }

    const socket = getSocket();

    if (!socket) {
        return;
    }

    const call = incomingCall.value;

    try {
        activeCallUser.value = call.fromUser;
        currentCallType.value = call.callType;

        const stream = await getLocalMediaStream(call.callType);
        const pc = createPeerConnection(call.fromUser.id);

        stream.getTracks().forEach((track) => {
            pc.addTrack(track, stream);
        });

        await pc.setRemoteDescription(new RTCSessionDescription(call.offer));
        await flushPendingIceCandidates();

        const answer = await pc.createAnswer();
        await pc.setLocalDescription(answer);

        socket.emit('call-answer', {
            toUserId: call.fromUser.id,
            answer,
        });

        incomingCall.value = null;
        callStatus.value = 'connected';
        await attachVideos();

    } catch (error) {
        console.error('No se pudo aceptar la llamada:', error);
        resetCallState();
    }
}

function rejectCall() {
    if (!incomingCall.value) {
        return;
    }

    const socket = getSocket();

    if (socket) {
        socket.emit('reject-call', {
            toUserId: incomingCall.value.fromUser.id,
        });
    }

    resetCallState();
}

function endCall() {
    const socket = getSocket();

    if (socket && groupCallActive.value && groupConversationId.value) {
        socket.emit('leave-group-call', {
            conversationId: groupConversationId.value,
        });

        resetCallState();

        return;
    }

    if (socket && activeCallUser.value) {
        socket.emit('end-call', {
            toUserId: activeCallUser.value.id,
        });
    }

    resetCallState();
}

async function joinActiveGroupCall(event: Event) {
    const customEvent = event as CustomEvent<{
        conversationId: number;
        members: CallUser[];
        fromUser: CallUser;
        group: GroupCallInfo;
        callType?: CallType;
    }>;

    const socket = getSocket();

    if (!socket) {
        return;
    }

    if (callStatus.value !== 'idle') {
        console.error('Ya hay una llamada en curso.');

        return;
    }

    try {
        const callType = customEvent.detail.callType ?? 'voice';

        await getGroupLocalMediaStream(callType);

        callStatus.value = 'connected';
        currentCallType.value = callType;
        groupCallActive.value = true;
        groupConversationId.value = customEvent.detail.conversationId;
        groupMembers.value = [];
        activeCallUser.value = null;
        activeGroupCallInfo.value = customEvent.detail.group;

        socket.emit('join-group-call', {
            conversationId: customEvent.detail.conversationId,
            fromUser: customEvent.detail.fromUser,
        });
    } catch (error) {
        console.error('No se pudo unir a la llamada grupal:', error);
        resetCallState();
    }
}

onMounted(() => {
    const socket = getSocket();

    if (!socket) {
        return;
    }

    window.addEventListener('destinario:start-call', startOutgoingCall);
    window.addEventListener('destinario:start-group-call', startOutgoingGroupCall);
    window.addEventListener('destinario:join-active-group-call', joinActiveGroupCall);

    socket.on('incoming-call', (data: IncomingCall) => {
        if (callStatus.value !== 'idle') {
            socket.emit('reject-call', {
                toUserId: data.fromUser.id,
            });

            return;
        }

        incomingCall.value = data;
        currentCallType.value = data.callType;
        callStatus.value = 'ringing';
    });

    socket.on('call-answer', async ({ answer }) => {
        if (!peerConnection.value) {
            return;
        }

        await peerConnection.value.setRemoteDescription(
            new RTCSessionDescription(answer),
        );

        clearOutgoingCallTimeout();
        callStatus.value = 'connected';
        await attachVideos();
    });

    socket.on('ice-candidate', async ({ candidate }) => {
        if (!candidate) {
            return;
        }

        if (!peerConnection.value) {
            pendingIceCandidates.value.push(candidate);

            return;
        }

        try {
            await peerConnection.value.addIceCandidate(
                new RTCIceCandidate(candidate),
            );
        } catch (error) {
            console.error('Error agregando ICE candidate:', error);
        }
    });

    socket.on('group-call-user-left', ({ userId, remainingParticipants }) => {
        closeGroupPeerConnection(Number(userId));

        setVisibleGroupMembers(remainingParticipants);
    });

    socket.on('group-call-participants-updated', async ({ conversationId, participants }) => {
        if (
            !groupCallActive.value ||
            Number(groupConversationId.value) !== Number(conversationId)
        ) {
            return;
        }

        await syncGroupParticipants(participants);
    });

    clearOutgoingCallTimeout();
    socket.on('call-rejected', () => {
        alert('La llamada fue rechazada.');
        resetCallState();
    });

    socket.on('call-ended', () => {
        alert('La llamada terminó.');
        resetCallState();
    });
   
    socket.on('incoming-group-call', (data: GroupIncomingCall) => {
        const isForMe =
            !data.toUserIds ||
            data.toUserIds.map(Number).includes(Number(authUser.value.id));

        if (!isForMe) {
            return;
        }

        if (Number(data.fromUser.id) === Number(authUser.value.id)) {
            return;
        }

        const hasRealActiveCall =
            groupCallActive.value ||
            activeCallUser.value !== null ||
            incomingCall.value !== null ||
            pendingOutgoingCall.value !== null;

        if (callStatus.value !== 'idle' && !hasRealActiveCall) {
            resetCallState();
        }

        if (callStatus.value !== 'idle') {
            return;
        }

        incomingGroupCall.value = data;
        currentCallType.value = data.callType;
        callStatus.value = 'ringing';
    });

    socket.on('group-call-user-joined', async ({ user }) => {
        if (!groupCallActive.value) {
            return;
        }

        if (Number(user.id) !== Number(authUser.value.id)) {
            groupMembers.value = [
                ...groupMembers.value.filter((member) => member.id !== user.id),
                user,
            ];
        }

        await createOfferForGroupUser(user);
    });

    socket.on('group-call-existing-participants', ({ participants }) => {
        if (!groupCallActive.value) {
            return;
        }

        setVisibleGroupMembers(participants);
    });

    socket.on('group-call-offer', handleGroupOffer);

    socket.on('group-call-answer', handleGroupAnswer);

    socket.on('group-ice-candidate', handleGroupIceCandidate);

    socket.on('group-call-ended', ({ conversationId }) => {
        if (
            !groupCallActive.value ||
            Number(groupConversationId.value) !== Number(conversationId)
        ) {
            return;
        }

        alert('La llamada grupal terminó.');
        resetCallState();
    });
});

onBeforeUnmount(() => {
    window.removeEventListener('destinario:start-call', startOutgoingCall);
    window.removeEventListener('destinario:start-group-call', startOutgoingGroupCall);
    window.removeEventListener('destinario:join-active-group-call', joinActiveGroupCall);

    const socket = getSocket();

    if (socket) {
        socket.off('incoming-call');
        socket.off('call-answer');
        socket.off('ice-candidate');
        socket.off('call-rejected');
        socket.off('call-ended');
        socket.off('incoming-group-call');
        socket.off('group-call-user-joined');
        socket.off('group-call-offer');
        socket.off('group-call-answer');
        socket.off('group-ice-candidate');
        socket.off('group-call-ended');
        socket.off('group-call-existing-participants');
        socket.off('group-call-user-left');
        socket.off('group-call-participants-updated');
    }

    resetCallState();
});
</script>

<template>
    <audio
        ref="remoteAudio"
        autoplay
        playsinline
    ></audio>

    <div
        v-if="missedCallMessage"
        class="fixed left-1/2 top-6 z-[99999] -translate-x-1/2 rounded-full bg-red-500 px-6 py-3 font-bold text-white shadow-lg"
    >
        {{ missedCallMessage }}
    </div>

    <IncomingCallModal
        v-if="incomingCall"
        mode="private"
        :photo="incomingCall.fromUser.profile_photo ?? null"
        :title="incomingCall.fromUser.username ?? 'Usuario'"
        :call-type="incomingCall.callType"
        @reject="rejectCall"
        @accept="acceptCall"
    />

    <IncomingCallModal
        v-if="incomingGroupCall"
        mode="group"
        :photo="incomingGroupCall.group.photo ?? null"
        :title="incomingGroupCall.group.name ?? 'Grupo'"
        :call-type="incomingGroupCall.callType"
        @reject="rejectGroupCall"
        @accept="acceptGroupCall"
    />

    <ActiveCallScreen
        v-if="callStatus === 'calling' || callStatus === 'connected'"
        v-model:local-video="localVideo"
        v-model:remote-video="remoteVideo"
        :call-status="callStatus"
        :current-call-type="currentCallType"
        :group-call-active="groupCallActive"
        :local-mic-enabled="localMicEnabled"
        :active-call-user="activeCallUser"
        :active-group-call-info="activeGroupCallInfo"
        :group-members="groupMembers"
        :local-stream="localStream"
        :group-remote-streams="groupRemoteStreams"
        @toggle-mic="toggleLocalMic"
        @end-call="endCall"
    />

    <OutgoingCallModal
        v-if="pendingOutgoingCall"
        mode="private"
        :photo="pendingOutgoingCall.toUser.profile_photo ?? null"
        :title="pendingOutgoingCall.toUser.username ?? 'Usuario'"
        :call-type="pendingOutgoingCall.callType"
        @cancel="cancelOutgoingCall"
        @confirm="confirmOutgoingCall"
    />

    <OutgoingCallModal
        v-if="pendingGroupCall"
        mode="group"
        :photo="pendingGroupCall.group.photo ?? null"
        :title="pendingGroupCall.group.name ?? 'Grupo'"
        :call-type="pendingGroupCall.callType"
        @cancel="pendingGroupCall = null"
        @confirm="confirmOutgoingGroupCall"
    />

</template>