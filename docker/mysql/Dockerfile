FROM mysql:8.0

# Copié dans l'image (pas monté) pour éviter
# « World-writable config file … is ignored » sous Docker Desktop Windows.
COPY charset.cnf /etc/mysql/conf.d/charset.cnf
RUN chmod 644 /etc/mysql/conf.d/charset.cnf
