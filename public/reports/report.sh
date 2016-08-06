#!/bin/bash
pdftk /reports/36_report.pdf /reports/36_sketch.pdf /reports/36_photos.pdf /reports/36_explanations.pdf  cat output /reports/36_final.pdf
chmod 777 /reports/36_final.pdf