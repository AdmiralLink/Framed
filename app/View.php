<?php

namespace Technicalpenguins\Framed;

class View extends Database {
    public function __construct() {
        parent::__construct();

        $this->photos = $this->db->query('SELECT * FROM photolib');

        $this->buildPage();
    }

    private function buildPage() {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Framed</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                }
                #container {
                    position: fixed;
                    width: 100vw;
                    height: 100vh;
                    top: 0;
                    left: 0;
                    background: black;
                }
                #container img {
                    position: absolute;
                    left: 0;
                    top: 0;
                    left: 50%;
                    top: 50%;
                    transform: translate(-50%, -50%);
                    max-width: 100%;
                    max-height: 100%;
                    opacity: 0;
                    z-index: 1;
                    transition: opacity 1.3s;
                }
                #container img.active {
                    opacity: 1;
                    z-index: 2;
                    transition: opacity 0.5s;
                }
            </style>
            <script src="https://js.pusher.com/7.0/pusher.min.js"></script>
        </head>
        <body>
            <div id="container">
            <?php while($row = $this->photos->fetchArray(SQLITE3_ASSOC)): ?>
                <img id="<?php print $row['id']; ?>" src="/photos/<?php print $row['filename']; ?>" />
            <?php endwhile; ?>
            </div>
            <script>
                let player = false;
                function reset() {
                    let active = document.getElementsByClassName('active');
                    if (active.length > 0) {
                        active[0].classList.remove('active');
                    }
                    if (player) {
                        window.clearInterval(player);
                    }
                    photos = document.getElementsByTagName('img');
                    if (photos.length > 0) {
                        photos[0].classList.add('active');
                    }
                    if (photos.length > 1) {
                        currentIdx = 0;
                        player = window.setInterval(() => {
                            if (currentIdx == photos.length-1) {
                                currentIdx = 0;
                            } else {
                                currentIdx++;
                            }
                            document.getElementsByClassName('active')[0].classList.remove('active');
                            photos[currentIdx].classList.add('active');
                        }, 30000);
                    }
                }

                reset();

                var pusher = new Pusher('<?php print $_ENV['PUSHER_KEY']; ?>', {
                    cluster: '<?php print $_ENV['PUSHER_CLUSTER']; ?>'
                });

                var channel = pusher.subscribe('photoView');

                channel.bind('new', function(data) {
                    for (var i = 0; i < data.length; i++) {
                        let img = document.createElement('img');
                        img.setAttribute('src', '/photos/' + data[i].filename);
                        img.setAttribute('id', data[i].id);
                        document.getElementById('container').append(img);
                    }
                    reset();
                });
                channel.bind('deleted', function(data) {
                    for (var i = 0; i < data.length; i++) {
                        document.getElementById(data[i].id).remove();
                    }
                    reset();
                });
            </script>
        </body>
        </html>
        <?php
    }
}