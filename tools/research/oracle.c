#define _POSIX_C_SOURCE 200809L

#include <glob.h>
#include <inttypes.h>
#include <math.h>
#include <stdint.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

#include "h3api.h"

#ifndef H3_RESEARCH_VERSION
#define H3_RESEARCH_VERSION "unknown"
#endif

#ifndef H3_RESEARCH_COMMIT
#define H3_RESEARCH_COMMIT "unknown"
#endif

#ifndef H3_RESEARCH_VECTORS
#define H3_RESEARCH_VECTORS "/opt/h3/tests/inputfiles"
#endif

typedef struct {
    double latDeg;
    double lngDeg;
    LatLng radians;
    int resolution;
} LatLngCase;

static void fail(const char *message) {
    fprintf(stderr, "h3 research oracle: %s\n", message);
    exit(EXIT_FAILURE);
}

static uint64_t monotonicNs(void) {
    struct timespec value;
    if (clock_gettime(CLOCK_MONOTONIC, &value) != 0) {
        fail("clock_gettime failed");
    }

    return (uint64_t)value.tv_sec * UINT64_C(1000000000) +
           (uint64_t)value.tv_nsec;
}

static H3Index parseIndex(const char *value) {
    H3Index index;
    if (sscanf(value, "%" SCNx64, &index) != 1) {
        fail("invalid H3 index");
    }

    return index;
}

static LatLngCase *readLatLngCases(size_t count) {
    LatLngCase *cases = calloc(count, sizeof(*cases));
    if (cases == NULL) {
        fail("cannot allocate coordinate cases");
    }

    char line[256];
    for (size_t i = 0; i < count; i++) {
        if (fgets(line, sizeof(line), stdin) == NULL ||
            sscanf(line, "%lf %lf %d", &cases[i].latDeg, &cases[i].lngDeg,
                   &cases[i].resolution) != 3) {
            free(cases);
            fail("invalid coordinate case");
        }

        cases[i].radians.lat = degsToRads(cases[i].latDeg);
        cases[i].radians.lng = degsToRads(cases[i].lngDeg);
    }

    return cases;
}

static H3Index *readIndexes(size_t count) {
    H3Index *indexes = calloc(count, sizeof(*indexes));
    if (indexes == NULL) {
        fail("cannot allocate cell cases");
    }

    char line[256];
    char value[32];
    for (size_t i = 0; i < count; i++) {
        if (fgets(line, sizeof(line), stdin) == NULL ||
            sscanf(line, "%31s", value) != 1) {
            free(indexes);
            fail("invalid cell case");
        }
        indexes[i] = parseIndex(value);
    }

    return indexes;
}

static void convertLatLng(size_t count) {
    LatLngCase *cases = readLatLngCases(count);
    for (size_t i = 0; i < count; i++) {
        H3Index index = 0;
        H3Error error = latLngToCell(&cases[i].radians, cases[i].resolution,
                                     &index);
        printf("%u %" PRIx64 "\n", error, index);
    }
    free(cases);
    fflush(stdout);
}

static void convertCells(size_t count) {
    H3Index *indexes = readIndexes(count);
    for (size_t i = 0; i < count; i++) {
        LatLng coordinate = {0};
        H3Error error = cellToLatLng(indexes[i], &coordinate);
        printf("%u %.17g %.17g\n", error, radsToDegs(coordinate.lat),
               radsToDegs(coordinate.lng));
    }
    free(indexes);
    fflush(stdout);
}

static void listPentagons(int resolution) {
    H3Index pentagons[12] = {0};
    H3Error error = getPentagons(resolution, pentagons);
    if (error != E_SUCCESS) {
        printf("error %u\n", error);
        fflush(stdout);
        return;
    }

    for (size_t i = 0; i < 12; i++) {
        LatLng center = {0};
        error = cellToLatLng(pentagons[i], &center);
        printf("%u %" PRIx64 " %.17g %.17g\n", error, pentagons[i],
               radsToDegs(center.lat), radsToDegs(center.lng));
    }
    fflush(stdout);
}

static void listBoundaries(size_t count) {
    H3Index *indexes = readIndexes(count);
    for (size_t i = 0; i < count; i++) {
        CellBoundary boundary = {0};
        H3Error error = cellToBoundary(indexes[i], &boundary);
        printf("%u %d", error, boundary.numVerts);
        for (int vertex = 0; vertex < boundary.numVerts; vertex++) {
            printf(" %.17g %.17g", radsToDegs(boundary.verts[vertex].lat),
                   radsToDegs(boundary.verts[vertex].lng));
        }
        putchar('\n');
    }
    free(indexes);
    fflush(stdout);
}

static void printVectorFile(const char *path) {
    FILE *file = fopen(path, "r");
    if (file == NULL) {
        fail("cannot open upstream vector file");
    }

    const char *name = strrchr(path, '/');
    name = name == NULL ? path : name + 1;

    char line[256];
    char index[32];
    double lat;
    double lng;
    while (fgets(line, sizeof(line), file) != NULL) {
        if (sscanf(line, "%31s %lf %lf", index, &lat, &lng) != 3) {
            fclose(file);
            fail("invalid upstream vector");
        }
        printf("%s %s %.17g %.17g\n", name, index, lat, lng);
    }

    fclose(file);
}

static void listUpstreamVectors(void) {
    const char *patterns[] = {
        H3_RESEARCH_VECTORS "/res*ic.txt",
        H3_RESEARCH_VECTORS "/rand*centers.txt",
        H3_RESEARCH_VECTORS "/bc*centers.txt",
    };

    for (size_t pattern = 0; pattern < sizeof(patterns) / sizeof(patterns[0]);
         pattern++) {
        glob_t matches = {0};
        int result = glob(patterns[pattern], 0, NULL, &matches);
        if (result != 0 && result != GLOB_NOMATCH) {
            globfree(&matches);
            fail("cannot list upstream vector files");
        }
        for (size_t file = 0; file < matches.gl_pathc; file++) {
            printVectorFile(matches.gl_pathv[file]);
        }
        globfree(&matches);
    }

    puts("end");
    fflush(stdout);
}

static uint64_t doubleBits(double value) {
    uint64_t bits;
    memcpy(&bits, &value, sizeof(bits));
    return bits;
}

static void benchmarkLatLng(size_t count, int warmups, int repeats) {
    LatLngCase *cases = readLatLngCases(count);
    volatile uint64_t checksum = 0;

    for (int warmup = 0; warmup < warmups; warmup++) {
        for (size_t i = 0; i < count; i++) {
            H3Index index = 0;
            latLngToCell(&cases[i].radians, cases[i].resolution, &index);
            checksum ^= index;
        }
    }

    for (int repeat = 0; repeat < repeats; repeat++) {
        uint64_t started = monotonicNs();
        for (size_t i = 0; i < count; i++) {
            H3Index index = 0;
            latLngToCell(&cases[i].radians, cases[i].resolution, &index);
            checksum ^= index;
        }
        uint64_t elapsed = monotonicNs() - started;
        printf("raw %" PRIu64 " %" PRIu64 "\n", elapsed, checksum);
    }

    for (int repeat = 0; repeat < repeats; repeat++) {
        uint64_t started = monotonicNs();
        for (size_t i = 0; i < count; i++) {
            LatLng coordinate = {.lat = degsToRads(cases[i].latDeg),
                                 .lng = degsToRads(cases[i].lngDeg)};
            H3Index index = 0;
            latLngToCell(&coordinate, cases[i].resolution, &index);
            checksum ^= index;
        }
        uint64_t elapsed = monotonicNs() - started;
        printf("degrees %" PRIu64 " %" PRIu64 "\n", elapsed, checksum);
    }

    puts("end");
    free(cases);
    fflush(stdout);
}

static void benchmarkCells(size_t count, int warmups, int repeats) {
    H3Index *indexes = readIndexes(count);
    volatile uint64_t checksum = 0;

    for (int warmup = 0; warmup < warmups; warmup++) {
        for (size_t i = 0; i < count; i++) {
            LatLng coordinate = {0};
            cellToLatLng(indexes[i], &coordinate);
            checksum ^= doubleBits(coordinate.lat) ^ doubleBits(coordinate.lng);
        }
    }

    for (int repeat = 0; repeat < repeats; repeat++) {
        uint64_t started = monotonicNs();
        for (size_t i = 0; i < count; i++) {
            LatLng coordinate = {0};
            cellToLatLng(indexes[i], &coordinate);
            checksum ^= doubleBits(coordinate.lat) ^ doubleBits(coordinate.lng);
        }
        uint64_t elapsed = monotonicNs() - started;
        printf("raw %" PRIu64 " %" PRIu64 "\n", elapsed, checksum);
    }

    puts("end");
    free(indexes);
    fflush(stdout);
}

static void printMetadata(void) {
    printf("h3_version\t%s\n", H3_RESEARCH_VERSION);
    printf("h3_commit\t%s\n", H3_RESEARCH_COMMIT);
    printf("compiler\t%s\n", __VERSION__);
    puts("end");
    fflush(stdout);
}

int main(void) {
    char line[256];
    char command[64];
    size_t count;
    int first;
    int second;

    while (fgets(line, sizeof(line), stdin) != NULL) {
        if (sscanf(line, "%63s", command) != 1) {
            continue;
        }

        if (strcmp(command, "meta") == 0) {
            printMetadata();
        } else if (strcmp(command, "latlng") == 0 &&
                   sscanf(line, "%*s %zu", &count) == 1) {
            convertLatLng(count);
        } else if (strcmp(command, "cell") == 0 &&
                   sscanf(line, "%*s %zu", &count) == 1) {
            convertCells(count);
        } else if (strcmp(command, "pentagons") == 0 &&
                   sscanf(line, "%*s %d", &first) == 1) {
            listPentagons(first);
        } else if (strcmp(command, "boundary") == 0 &&
                   sscanf(line, "%*s %zu", &count) == 1) {
            listBoundaries(count);
        } else if (strcmp(command, "vectors") == 0) {
            listUpstreamVectors();
        } else if (strcmp(command, "bench-latlng") == 0 &&
                   sscanf(line, "%*s %zu %d %d", &count, &first, &second) ==
                       3) {
            benchmarkLatLng(count, first, second);
        } else if (strcmp(command, "bench-cell") == 0 &&
                   sscanf(line, "%*s %zu %d %d", &count, &first, &second) ==
                       3) {
            benchmarkCells(count, first, second);
        } else if (strcmp(command, "quit") == 0) {
            return EXIT_SUCCESS;
        } else {
            fail("unknown command");
        }
    }

    return EXIT_SUCCESS;
}
