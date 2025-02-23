<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>File Menu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            margin-left: 20px;
            white-space: nowrap;
            height: 100vh;
            overflow: hidden;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            color: black;
        }

        li.non-runnable {
            color: grey;
        }

        li:hover {
            color: blue;
        }

        li.active {
            color: darkblue;
            font-weight: bold;
        }

        .folder {
            cursor: pointer;
            color: orange;
        }

        .folder ul {
            display: none;
            margin-left: 20px;
        }

        .folder.open ul {
            display: block;
        }

        .header {
            width: 100%;
            background-color: #0056b3;
            color: white;
            height: 40px;
            box-sizing: border-box;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            padding: 0 20px;
            display: flex;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header .menu-title {
            font-size: 20px;
            font-weight: bold;
            line-height: 1;
        }
    </style>
    <script>
        function loadFile(file) {
            if (file.endsWith('.php') || file.endsWith('.html')) {
                document.getElementById('iframe').src = file;
                setActive(file);
            }
        }

        function refreshMenu() {
            location.reload();
        }

        function sortMenu(criteria) {
            const menu = document.getElementById('menu');
            const items = Array.from(menu.getElementsByTagName('li'));
            items.sort((a, b) => {
                if (criteria === 'extension') {
                    return a.dataset.extension.localeCompare(b.dataset.extension);
                } else if (criteria === 'date') {
                    return new Date(b.dataset.date) - new Date(a.dataset.date);
                } else {
                    return a.textContent.localeCompare(b.textContent);
                }
            });
            items.forEach(item => menu.appendChild(item));
        }

        function setActive(file) {
            const items = document.querySelectorAll('#menu li');
            items.forEach(item => {
                if (item.textContent === file) {
                    item.classList.add('active');
                } else {
                    item.classList.remove('active');
                }
            });
        }

        function toggleFolder(event) {
            const folder = event.currentTarget;
            folder.classList.toggle('open');
        }
    </script>
</head>

<body>
    <div style="display: flex;">
        <div style="width: 250px;">
            <div>
                <button onclick="refreshMenu()">Refresh Menu</button>
                <button onclick="sortMenu('extension')">Sort by Extension</button>
                <button onclick="sortMenu('date')">Sort by Date</button>
                <button onclick="sortMenu('alphabetical')">Sort Alphabetically</button>
            </div>
            <ul id="menu">
                <?php
                function listFiles($dir)
                {
                    $files = array_diff(scandir($dir), array('.', '..'));
                    $runnable = [];
                    $nonRunnable = [];
                    foreach ($files as $file) {
                        $path = "$dir/$file";
                        if (is_dir($path)) {
                            echo "<li class=\"folder\" onclick=\"toggleFolder(event)\">$file<ul>";
                            listFiles($path);
                            echo "</ul></li>";
                        } else {
                            $extension = pathinfo($file, PATHINFO_EXTENSION);
                            $date = date("Y-m-d H:i:s", filemtime($path));
                            $tooltip = strlen($file) > 20 ? "title=\"$file\"" : "";
                            if ($extension === 'php' || $extension === 'html') {
                                $runnable[] = "<li data-extension=\"$extension\" data-date=\"$date\" onclick=\"loadFile('$path')\" $tooltip>$file</li>";
                            } else {
                                $nonRunnable[] = "<li class=\"non-runnable\" data-extension=\"$extension\" data-date=\"$date\" $tooltip>$file</li>";
                            }
                        }
                    }
                    echo implode("\n", $runnable);
                    echo "<hr>";
                    echo implode("\n", $nonRunnable);
                }

                listFiles('.');
                ?>
            </ul>
        </div>
        <div style="flex-grow: 1; overflow-y: auto; height: 100vh;">
            <iframe id="iframe" style="width: 100%; height: 100vh;"></iframe>
        </div>
    </div>
</body>

</html>