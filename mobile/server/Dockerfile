FROM gradle:8.11.1-jdk17 AS build
WORKDIR /workspace
COPY . .
RUN gradle :server:installDist --no-daemon

FROM eclipse-temurin:17-jre
WORKDIR /app
COPY --from=build /workspace/server/build/install/server /app
EXPOSE 8080
ENTRYPOINT ["/app/bin/server"]
