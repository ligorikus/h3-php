FROM alpine:3.22@sha256:14358309a308569c32bdc37e2e0e9694be33a9d99e68afb0f5ff33cc1f695dce AS build

ARG H3_VERSION=4.5.0
ARG H3_COMMIT=1b536c34225191ba24a75a840f634d4a48c3b206
ARG H3_ARCHIVE_SHA256=0da8a392a6ff77e76b60e6a331a49497d0935b6b7b6899da7a3e2786139b0441

RUN apk add --no-cache build-base=0.5-r3 cmake=3.31.7-r1 curl=8.14.1-r3
RUN curl --fail --location --silent --show-error \
        "https://github.com/uber/h3/archive/refs/tags/v${H3_VERSION}.tar.gz" \
        --output /tmp/h3.tar.gz
RUN echo "${H3_ARCHIVE_SHA256}  /tmp/h3.tar.gz" | sha256sum -c -
RUN mkdir /opt/h3-src && tar --extract --gzip --file /tmp/h3.tar.gz \
        --directory /opt/h3-src --strip-components=1
RUN cmake -S /opt/h3-src -B /opt/h3-build \
        -DCMAKE_BUILD_TYPE=Release \
        -DBUILD_BENCHMARKS=OFF \
        -DBUILD_FILTERS=OFF \
        -DBUILD_FUZZERS=OFF \
        -DBUILD_GENERATORS=OFF \
        -DBUILD_TESTING=OFF \
        -DENABLE_DOCS=OFF \
        -DENABLE_FORMAT=OFF \
        -DENABLE_LINTING=OFF
RUN cmake --build /opt/h3-build --parallel

COPY oracle.c /tmp/oracle.c
RUN cc -O3 -DNDEBUG \
        -DH3_RESEARCH_VERSION=\"${H3_VERSION}\" \
        -DH3_RESEARCH_COMMIT=\"${H3_COMMIT}\" \
        -I/opt/h3-src/src/h3lib/include \
        -I/opt/h3-build/src/h3lib/include \
        /tmp/oracle.c /opt/h3-build/lib/libh3.a -lm \
        -o /tmp/h3-research-oracle

RUN mkdir -p /opt/h3/tests/inputfiles && \
    cp /opt/h3-src/tests/inputfiles/res*ic.txt /opt/h3/tests/inputfiles/ && \
    cp /opt/h3-src/tests/inputfiles/rand*centers.txt /opt/h3/tests/inputfiles/ && \
    cp /opt/h3-src/tests/inputfiles/bc*centers.txt /opt/h3/tests/inputfiles/

FROM alpine:3.22@sha256:14358309a308569c32bdc37e2e0e9694be33a9d99e68afb0f5ff33cc1f695dce

RUN apk add --no-cache libgcc=14.2.0-r6
COPY --from=build /tmp/h3-research-oracle /usr/local/bin/h3-research-oracle
COPY --from=build /opt/h3/tests/inputfiles /opt/h3/tests/inputfiles

ENTRYPOINT ["/usr/local/bin/h3-research-oracle"]
