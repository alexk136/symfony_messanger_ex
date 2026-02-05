# Simple messanger example on websockets

Максимально простой пример мессенджера на примере общения пользователя и оператора, отправка сообщений реализована через сокеты (только). В рамках примеры многие решения, такие как: обновление чата через аппенд сообщений, регистрации польоватлей, аватары и т.д. только минимально рабочая схема. 

## Как запустить

- Docker Compose
- Порты
80 - Nginx (веб-интерфейс)
5433 - PostgreSQL
8080 - WebSocket сервер
9000 - PHP-FPM

## Запуск

```bash
docker-compose up -d --build
```

## Как работать с UI

Откройте http://localhost

Для клиента:
1. Выберите "Login as Client" (в рамках теста вся логика упрощена)
2. Введите email и имя
3. Сообщения обновляются через WebSocket

Для оператора:
1. Выберите "Login as Operator"
2. Введите email и имя
3. Увидите список открытых чатов
4. В данной реализации чат назначается автоматически при открытии его оператором.
5. Закрыть чат можно через кнопку "Close Chat"


## Список эндпоинтов

### Users
- POST /api/users/login - Логин пользователя (client/operator)
Body: {"email": "user@example.com", "name": "User Name", "role": "client"}

### Chats
- POST /api/chats - Создать новый чат (client/operator)
- GET /api/chats - Получить список чатов
Query: ?limit=50
- POST /api/chats/{id}/close - Закрыть чат (только для операторов)
- POST /api/chats/{id}/messages - Отправить сообщение
Body: {"text": "Message text", "clientMsgId": "unique-id"}
- GET /api/chats/{id}/messages - Получить сообщения чата
Query: ?limit=50&beforeId={msgId}&afterId={msgId}
- POST /api/chats/{id}/assign-operator - Назначить оператора на чат


### UI Routes
- GET / - Главная страница
- GET /login - Страница логина клиента
- GET /login-operator - Страница логина оператора
- GET /chats/{user_id} - Интерфейс чата


### WebSocket
 - URL: ws://localhost:8080

## Индексы базы данных

### Message
 - uniq_client_msg_user (clientMsgId, userId) - индекс предотвращает дублирование сообщений при повторных отправках от одного пользователя
 - idx_message_chat_id (chat_id, id) - оптимизирует выборку сообщений по чату с сортировкой по id (в getMessages)
 - idx_message_status (status) - для фильтрации по статусу (новые/прочитанные)
 - idx_message_created_at (created_at) - для сортировки по времени создания

### Chat
 - idx_chat_user_last_msg (userId, lastMessageAt) - для выборки клиентских чатов и сортировки
 - idx_chat_operator_last_msg (operatorId, lastMessageAt) - для выборки операторских чатов и сортировки
 - idx_chat_status (status) - фильтрация открытых/закрытых чатов
 - idx_chat_last_message_at (lastMessageAt) - глобальная сортировка чатов по активности

### User
- idx_user_email_role (email, role) - ускоряем запрос логина, который фильтрует одновременно по email и role



## Решение конкуренции send/close

Решение вопроса race condition при одновременной отправке сообщения, закрытия чата и т.д. решаем через pessimistic locking на уровне бд. 