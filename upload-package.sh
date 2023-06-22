#!/bin/bash

set -e

scp *.deb deb:/srv/repos/apt/debian/incoming/bullseye/
ssh deb "/srv/repos/apt/debian/incoming/consume.sh"
