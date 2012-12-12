rtorrent_auto_move
==================

Скрипт для автоматическое переименование торрентов, скачанных программой rTorrent.

Имя файла формируется из <title> страницы прописанной в комментарии *.torrent файла

### Установка
1. Добавить в `/home/user/.rtorrent.rc` строку: 
    `system.method.set_key = event.download.finished,auto_move,"execute=/home/user/rtorrent_auto_move/tor.php,$d.get_base_path=,$d.get_name=,$d.get_custom2="`
2. Перезапустить rtorrent: `sudo /etc/init.d/rtorrentInit.sh restart`
3. Загрузить скрипт `tor.php` в `/home/user/rtorrent_auto_move/tor.php`
4. Дать права за запуск: `chmod +x /home/user/rtorrent_auto_move/tor.php`
