FROM php:8.3-cli

# ติดตั้ง ffmpeg
RUN apt-get update && apt-get install -y ffmpeg python3 curl

# ติดตั้ง yt-dlp
RUN curl -L https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp -o /usr/local/bin/yt-dlp \
    && chmod a+rx /usr/local/bin/yt-dlp

WORKDIR /app
COPY . .

RUN mkdir -p uploads output

EXPOSE 8080
CMD ["php", "-S", "0.0.0.0:8080"]