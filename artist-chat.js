(() => {
    const root = document.getElementById('artist-chat');
    if (!root) return;

    const api = root.dataset.api || 'artist-chat-api.php';
    const panel = document.getElementById('artist-chat-panel');
    const launcher = root.querySelector('[data-chat-open]');
    const roomsNode = document.getElementById('artist-chat-rooms');
    const conversation = document.getElementById('artist-chat-conversation');
    const emptyNode = document.getElementById('artist-chat-empty');
    const titleNode = document.getElementById('artist-chat-room-title');
    const roomNameNode = document.getElementById('artist-chat-room-name');
    const memberToggle = document.getElementById('artist-chat-members-toggle');
    const memberCountNode = document.getElementById('artist-chat-member-count');
    const membersNode = document.getElementById('artist-chat-members');
    const privateBack = document.getElementById('artist-chat-private-back');
    const privateNote = document.getElementById('artist-chat-private-note');
    const messagesNode = document.getElementById('artist-chat-messages');
    const compose = document.getElementById('artist-chat-compose');
    const input = document.getElementById('artist-chat-input');
    const status = document.getElementById('artist-chat-status');
    const count = document.getElementById('artist-chat-count');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let activeRoom = null;
    let activePrivateUser = null;
    let lastMessageId = 0;
    let pollTimer = null;
    let rooms = [];
    let members = [];

    const setStatus = (message = '') => { status.textContent = message; };
    const request = async (url, options = {}) => {
        const response = await fetch(url, { credentials: 'same-origin', ...options });
        let data;
        try { data = await response.json(); } catch { throw new Error('Сервер вернул некорректный ответ.'); }
        if (!response.ok) throw new Error(data.error || 'Не удалось выполнить запрос.');
        return data;
    };
    const post = (action, values = {}) => {
        const body = new URLSearchParams({ action, csrf_token: csrf, ...values });
        return request(api, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' }, body });
    };
    const stopPolling = () => {
        if (pollTimer) window.clearInterval(pollTimer);
        pollTimer = null;
    };
    const setOpen = (isOpen) => {
        panel.hidden = !isOpen;
        launcher.setAttribute('aria-expanded', String(isOpen));
        if (isOpen) loadRooms();
        else stopPolling();
    };

    const renderRooms = () => {
        roomsNode.replaceChildren();
        count.textContent = String(rooms.length);
        count.hidden = rooms.length === 0;
        if (!rooms.length) {
            const hint = document.createElement('p');
            hint.className = 'artist-chat-room-hint';
            hint.textContent = 'Здесь появятся чаты, в которые вы вступили.';
            roomsNode.append(hint);
            return;
        }
        rooms.forEach((room) => {
            const row = document.createElement('div');
            row.className = 'artist-chat-room-row';
            const select = document.createElement('button');
            select.type = 'button';
            select.className = 'artist-chat-room-select';
            select.setAttribute('aria-current', activeRoom?.id === room.id ? 'true' : 'false');
            if (room.image) {
                const image = document.createElement('img');
                image.src = room.image;
                image.alt = '';
                image.loading = 'lazy';
                select.append(image);
            } else {
                const avatar = document.createElement('span');
                avatar.className = 'artist-chat-room-fallback';
                avatar.textContent = 'SF';
                select.append(avatar);
            }
            const labels = document.createElement('span');
            labels.className = 'artist-chat-room-labels';
            const name = document.createElement('span');
            name.className = 'artist-chat-room-name';
            name.textContent = room.companyname;
            const roomCount = document.createElement('span');
            roomCount.className = 'artist-chat-room-count';
            roomCount.textContent = `${room.member_count} участников`;
            labels.append(name, roomCount);
            select.append(labels);
            select.addEventListener('click', () => selectRoom(room));
            const leave = document.createElement('button');
            leave.type = 'button';
            leave.className = 'artist-chat-room-leave';
            leave.setAttribute('aria-label', `Выйти из чата ${room.companyname}`);
            leave.title = 'Выйти из чата';
            leave.textContent = '×';
            leave.addEventListener('click', () => leaveRoom(room));
            row.append(select, leave);
            roomsNode.append(row);
        });
    };

    const loadRooms = async () => {
        try {
            const data = await request(`${api}?action=list`);
            rooms = data.rooms || [];
            if (activeRoom) activeRoom = rooms.find((room) => room.id === activeRoom.id) || null;
            renderRooms();
            if (!activeRoom) {
                activePrivateUser = null;
                stopPolling();
                showEmpty('Выберите чат слева или вступите в чат из карточки артиста.');
            }
            setStatus('');
        } catch (error) {
            if (error.message.includes('Войдите')) {
                roomsNode.replaceChildren();
                showEmpty('Войдите в аккаунт, затем вступите в чат из карточки артиста.');
                setStatus('Для общения нужен аккаунт SoundFusion.');
                return;
            }
            setStatus(error.message);
        }
    };

    const showEmpty = (message) => {
        emptyNode.textContent = message;
        emptyNode.hidden = false;
        titleNode.hidden = true;
        membersNode.hidden = true;
        privateNote.hidden = true;
        messagesNode.replaceChildren();
        compose.hidden = true;
    };

    const renderMembers = () => {
        membersNode.replaceChildren();
        members.forEach((member) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'artist-chat-member';
            if (member.avatar) {
                const image = document.createElement('img');
                image.src = member.avatar;
                image.alt = '';
                image.loading = 'lazy';
                button.append(image);
            } else {
                const avatar = document.createElement('span');
                avatar.className = 'artist-chat-member-fallback';
                avatar.textContent = (member.display_name || member.username || '?').trim().slice(0, 1).toUpperCase();
                button.append(avatar);
            }
            const name = document.createElement('span');
            name.textContent = member.mine ? `${member.display_name} · Вы` : member.display_name;
            button.append(name);
            if (member.mine) {
                button.disabled = true;
            } else {
                button.addEventListener('click', () => openPrivateUser(member));
            }
            membersNode.append(button);
        });
    };

    const loadMembers = async () => {
        if (!activeRoom || activePrivateUser) return;
        try {
            const data = await request(`${api}?action=members&artist_id=${encodeURIComponent(activeRoom.id)}`);
            members = data.members || [];
            activeRoom.member_count = Number(data.count) || members.length;
            memberCountNode.textContent = String(activeRoom.member_count);
            renderRooms();
            renderMembers();
        } catch (error) {
            setStatus(error.message);
        }
    };

    const selectRoom = (room) => {
        activeRoom = room;
        activePrivateUser = null;
        lastMessageId = 0;
        membersNode.hidden = true;
        memberToggle.hidden = false;
        privateBack.hidden = true;
        privateNote.hidden = true;
        memberToggle.setAttribute('aria-expanded', 'false');
        messagesNode.replaceChildren();
        roomNameNode.textContent = room.companyname;
        memberCountNode.textContent = String(room.member_count || 0);
        titleNode.hidden = false;
        emptyNode.hidden = true;
        compose.hidden = false;
        input.placeholder = 'Сообщение в общем чате…';
        renderRooms();
        stopPolling();
        loadMembers();
        loadMessages(true);
        pollTimer = window.setInterval(() => {
            loadMessages(false);
            if (!activePrivateUser) loadMembers();
        }, 7000);
        if (window.matchMedia('(max-width: 720px)').matches) conversation.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const appendMessage = (message) => {
        const bubble = document.createElement('article');
        bubble.className = `artist-chat-message${message.mine ? ' is-mine' : ''}`;
        const meta = document.createElement('div');
        meta.className = 'artist-chat-message-meta';
        if (!activePrivateUser && !message.mine && Number(message.sender_id) > 0) {
            const author = document.createElement('button');
            author.type = 'button';
            author.className = 'artist-chat-message-author';
            author.textContent = message.display_name || message.username || 'Участник';
            author.title = 'Написать личное сообщение';
            author.addEventListener('click', () => openPrivateUser({ id: Number(message.sender_id), display_name: message.display_name || message.username || 'Участник' }));
            meta.append(author);
        } else {
            const author = document.createElement('strong');
            author.textContent = message.display_name || message.username || 'Участник';
            meta.append(author);
        }
        const time = document.createElement('time');
        const date = new Date(String(message.created_at).replace(' ', 'T'));
        time.dateTime = Number.isNaN(date.getTime()) ? '' : date.toISOString();
        time.textContent = Number.isNaN(date.getTime()) ? '' : date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        meta.append(time);
        const text = document.createElement('p');
        text.textContent = message.message;
        bubble.append(meta, text);
        messagesNode.append(bubble);
    };

    const loadMessages = async (scrollToBottom) => {
        if (!activeRoom) return;
        const action = activePrivateUser ? 'private_messages' : 'messages';
        const peer = activePrivateUser ? `&recipient_id=${encodeURIComponent(activePrivateUser.id)}` : '';
        try {
            const data = await request(`${api}?action=${action}&artist_id=${encodeURIComponent(activeRoom.id)}${peer}&after=${lastMessageId}`);
            (data.messages || []).forEach((message) => {
                appendMessage(message);
                lastMessageId = Math.max(lastMessageId, Number(message.id) || 0);
            });
            if (scrollToBottom || data.messages?.length) messagesNode.scrollTop = messagesNode.scrollHeight;
            setStatus('');
        } catch (error) {
            setStatus(error.message);
        }
    };

    const openPrivateUser = (member) => {
        if (!activeRoom || Number(member.id) < 1) return;
        activePrivateUser = { id: Number(member.id), display_name: member.display_name || member.username || 'Участник' };
        lastMessageId = 0;
        roomNameNode.textContent = `Личное сообщение · ${activePrivateUser.display_name}`;
        memberToggle.hidden = true;
        membersNode.hidden = true;
        privateBack.hidden = false;
        privateNote.hidden = false;
        input.placeholder = 'Личное сообщение…';
        messagesNode.replaceChildren();
        stopPolling();
        loadMessages(true);
        pollTimer = window.setInterval(() => loadMessages(false), 5000);
        input.focus();
    };

    const returnToGroup = () => {
        if (!activeRoom) return;
        activePrivateUser = null;
        lastMessageId = 0;
        roomNameNode.textContent = activeRoom.companyname;
        memberToggle.hidden = false;
        privateBack.hidden = true;
        privateNote.hidden = true;
        input.placeholder = 'Сообщение в общем чате…';
        messagesNode.replaceChildren();
        stopPolling();
        loadMessages(true);
        pollTimer = window.setInterval(() => {
            loadMessages(false);
            if (!activePrivateUser) loadMembers();
        }, 7000);
    };

    const joinRoom = async (artistId) => {
        try {
            await post('join', { artist_id: String(artistId) });
            setOpen(true);
            await loadRooms();
            const room = rooms.find((item) => item.id === Number(artistId));
            if (room) selectRoom(room);
            setStatus('Вы вступили в чат артиста.');
        } catch (error) {
            setStatus(error.message);
            setOpen(true);
        }
    };

    const leaveRoom = async (room) => {
        if (!window.confirm(`Выйти из чата ${room.companyname}?`)) return;
        try {
            await post('leave', { artist_id: String(room.id) });
            rooms = rooms.filter((item) => item.id !== room.id);
            if (activeRoom?.id === room.id) {
                activeRoom = null;
                activePrivateUser = null;
                stopPolling();
                showEmpty('Вы вышли из чата. Чтобы вернуться, снова вступите из карточки артиста.');
            }
            renderRooms();
        } catch (error) {
            setStatus(error.message);
        }
    };

    launcher.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('[data-chat-close]')?.addEventListener('click', () => setOpen(false));
    memberToggle.addEventListener('click', async () => {
        const willOpen = membersNode.hidden;
        membersNode.hidden = !willOpen;
        memberToggle.setAttribute('aria-expanded', String(willOpen));
        if (willOpen) await loadMembers();
    });
    privateBack.addEventListener('click', returnToGroup);
    document.addEventListener('click', (event) => {
        const joinButton = event.target.closest('[data-chat-join]');
        if (joinButton) joinRoom(joinButton.dataset.chatJoin);
    });
    compose.addEventListener('submit', async (event) => {
        event.preventDefault();
        const message = input.value.trim();
        if (!activeRoom || !message) return;
        const submit = compose.querySelector('button[type="submit"]');
        submit.disabled = true;
        const isPrivate = Boolean(activePrivateUser);
        try {
            await post(isPrivate ? 'private_send' : 'send', {
                artist_id: String(activeRoom.id),
                ...(isPrivate ? { recipient_id: String(activePrivateUser.id) } : {}),
                message,
            });
            input.value = '';
            await loadMessages(true);
        } catch (error) {
            setStatus(error.message);
        } finally {
            submit.disabled = false;
            input.focus();
        }
    });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            compose.requestSubmit();
        }
    });
})();
