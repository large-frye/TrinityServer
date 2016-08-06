#!/bin/bash
pdftk /reports/2_report.pdf /reports/2_sketch.pdf /reports/2_photos.pdf cat output /reports/2_final.pdf
chmod 777 /reports/2_final.pdf