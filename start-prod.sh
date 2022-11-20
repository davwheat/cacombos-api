#!/bin/bash

docker compose --file docker-compose.prod.yml down
docker compose --file docker-compose.prod.yml up --build
