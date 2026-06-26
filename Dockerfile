FROM php:8.3-cli

RUN apt-get update && apt-get install -y ffmpeg python3 curl

RUN curl -L "https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp" -o /usr/local/bin/yt-dlp

RUN chmod a+rx /usr/local/bin/yt-dlp

WORKDIR /app

COPY . /app/

RUN mkdir -p /app/uploads /app/output && chmod 777 /app/uploads /app/output

EXPOSE 8080

CMD ["php", "-S", "0.0.0.0:8080", "-t", "/app", "-c", "/app/php.ini"]