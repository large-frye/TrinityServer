FROM ubuntu:14.04

RUN mkdir /reports && chown -R 777 /reports
RUN apt-get update && apt-get install -y \
  pdftk

ENTRYPOINT ["pdftk"]