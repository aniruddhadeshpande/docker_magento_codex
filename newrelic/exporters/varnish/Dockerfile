FROM varnish:7

USER root

RUN apt-get update \
    && apt-get install -y --no-install-recommends python3 \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY varnish_exporter.py /usr/local/bin/varnish_exporter.py

USER varnish

EXPOSE 9131

CMD ["python3", "/usr/local/bin/varnish_exporter.py"]
