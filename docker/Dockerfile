FROM debian:bullseye

# Update & upgrade existing packages
# Also pre-loads the dependencies of the source package in order to not having to re-download them on every source change
RUN export DEBIAN_FRONTEND=noninteractive \
  && apt update \
  && apt upgrade -y \
  && apt install -y php-cli php-xml php-curl ffmpeg imagemagick

# Copy additional, docker-only files
#COPY ./docker/data/. /

# Copy source files, build the package and install
COPY ./yap/ /tmp/package/

# Build the package, install and clean up
RUN cd /tmp \
  && dpkg-deb --build "package" "package.deb" \
  && rm -r /tmp/package \
  && apt install -y /tmp/package.deb \
  && rm /tmp/package.deb

# Set entrypoint wrapper
CMD ["sleep", "infinity"]
