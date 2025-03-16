FROM python:3.10

# Set working directory
WORKDIR /var/www/TA_201_Flask

# Copy Flask files
COPY TA_201_Flask /var/www/TA_201_Flask

# Upgrade pip dan install dependencies
RUN pip install --upgrade pip setuptools wheel

# Install dependencies dari `requirements.txt`
RUN pip install --no-cache-dir -r /var/www/TA_201_Flask/requirements.txt

# Download model Spacy
RUN python -m spacy download en_core_web_sm

# Expose Flask port
EXPOSE 5000

# Jalankan aplikasi Flask dengan virtual environment
CMD ["python", "/var/www/TA_201_Flask/app.py"]