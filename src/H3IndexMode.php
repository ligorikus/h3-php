<?php

declare(strict_types=1);

namespace H3;

enum H3IndexMode: int
{
    case H3_CELL_MODE = 1;
    case H3_DIRECTEDEDGE_MODE = 2;
    case H3_EDGE_MODE = 3;
    case H3_VERTEX_MODE = 4;
}