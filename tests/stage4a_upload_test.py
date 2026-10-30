"""Isolated multipart HTTP tests; no database or live upload files are touched."""
import base64, http.client, pathlib, socket, subprocess, tempfile, time
ROOT = pathlib.Path(__file__).resolve().parents[1]
PHP = r"C:/xampp/php/php.exe"
with tempfile.TemporaryDirectory(prefix="stage4a-") as temp:
    router = pathlib.Path(temp) / "router.php"
    helper = (ROOT / "data/image_upload.php").as_posix()
    router.write_text("<?php require " + repr(helper) + "; try { [$im, $ext] = validatedUploadImage($_FILES['photo'] ?? [], 2097152); ob_start(); imagepng($im); $out=ob_get_clean(); imagedestroy($im); echo strpos($out, '<?php') === false ? 'ACCEPT' : 'PAYLOAD'; } catch (Throwable $e) { http_response_code(400); echo 'REJECT'; }", encoding="utf-8")
    with socket.socket() as sock:
        sock.bind(('127.0.0.1', 0)); port = sock.getsockname()[1]
    server = subprocess.Popen([PHP, '-d', 'extension=gd', '-S', f'127.0.0.1:{port}', str(router)], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    try:
        for _ in range(50):
            try:
                with socket.create_connection(('127.0.0.1', port), timeout=.2): break
            except OSError: time.sleep(.1)
        image = base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=')
        cases = [('photo.png',image,200), ('photo.PNG',image,200), ('photo.php.png',image,400), ('photo.png.php',image,400), ('photo.jpg',image,400), ('photo.png',b'<?php echo 1;',400), ('photo.svg',b'<svg/>',400), ('photo.png',image+b'<?php echo 1;?>',200), ('../photo.png',image,400), ('photo.png',image+b'x'*2097152,400)]
        for name, data, expected in cases:
            boundary='stage4a-boundary'
            body=(f'--{boundary}\r\nContent-Disposition: form-data; name="photo"; filename="{name}"\r\nContent-Type: image/png\r\n\r\n'.encode()+data+f'\r\n--{boundary}--\r\n'.encode())
            conn=http.client.HTTPConnection('127.0.0.1',port,timeout=5)
            conn.request('POST','/',body,{'Content-Type':f'multipart/form-data; boundary={boundary}'})
            response=conn.getresponse(); result=response.read(); conn.close()
            # PHP strips client path prefixes in multipart names; ../photo.png becomes photo.png.
            if name == '../photo.png': expected=200
            assert response.status == expected and result == (b'ACCEPT' if expected==200 else b'REJECT'), (name,response.status,result)
        print(f'{len(cases)} multipart HTTP checks passed (PHP built-in server, GD explicitly enabled; not Apache).')
    finally:
        server.terminate(); server.wait(timeout=5)
