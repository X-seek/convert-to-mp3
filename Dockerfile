FROM php:8.3-cli

RUN apt-get update && apt-get install -y ffmpeg python3 curl

RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

WORKDIR /app
COPY . .

RUN mkdir -p uploads output
RUN chmod +x start.sh

CMD ["sh", "start.sh"]