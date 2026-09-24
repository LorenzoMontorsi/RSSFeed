FROM freshrss/freshrss:1.30.0

COPY extensions/xExtension-LanguageCatalog /opt/freshrss-bundle/xExtension-LanguageCatalog
COPY config/config.custom.php /opt/freshrss-bundle/config.custom.php
COPY config/config-user.custom.php /opt/freshrss-bundle/config-user.custom.php
COPY docker/enable-extension.php /opt/freshrss-bundle/enable-extension.php
COPY docker/entrypoint-wrapper.sh /entrypoint-wrapper.sh

RUN sed -i 's/\r$//' /entrypoint-wrapper.sh && chmod +x /entrypoint-wrapper.sh

ENTRYPOINT ["/entrypoint-wrapper.sh"]
