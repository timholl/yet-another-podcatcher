#!/bin/bash

SRC_DIR="yap"
VERSION=$(awk '/^Version:/ { print $2 }' ${SRC_DIR}/DEBIAN/control)

dpkg-deb --build "${SRC_DIR}" "${SRC_DIR}_${VERSION}.deb"
